# ملف الإصلاح الكامل — حكيم × ماهر
### كل ما يجب تعديله في النظام ليعمل الوكيل ١٠٠٪

هذا الملف مكتوب ليُسلَّم لمطوّر. كل شيء فيه **متحقَّق منه بالقياس** على النظام الحقيقي، لا بالتخمين. أسماء الحقول والأكواد مأخوذة من قاعدة البيانات والكود الفعلي.

---

# الفهرس

```
الجزء ١ — الحجز            ★ يحجب الوظيفة الأساسية، ابدأ به
  ١.١  البحث: أضف المعرّفات المفقودة
  ١.٢  المواعيد: معاملات اختيارية + قائمة الخيارات
  ١.٣  الحجز: مسار واجهة الوكيل
  ١.٤  النماذج: حقول حسب نوع الخدمة
  ١.٥  اختبارات القبول

الجزء ٢ — كلمة المرور: التحقق قبل الإرسال

الجزء ٣ — سجلات المحادثة

الجزء ٤ — مرجع الحقول والمتغيرات (اسحب منه، لا تخمّن)
```

---

# الجزء ١ — الحجز

## ١.٠ المشكلة بحقائقها

### البحث لا يرجّع معرّفات

```
GET /api/agent/v1/search?type=doctors&q=عيون   → 200

{
  "slug": "dr-george-kamel", "name": "د. جورج كامل", "fee": 350,
  "specialty": "عيون", "clinics": ["عيادة الأقصر للعيون"], "cities": ["الأقصر"],
  "book_url": "http://eg.hakeem.com.sa/book/doctors/dr-george-kamel"
}
```

`clinics` مصفوفة **أسماء نصية** — لا معرّف واحد.

### المواعيد تطلب معرّفات لا يملكها الوكيل

```
GET /api/agent/v1/doctors/dr-george-kamel/slots   → 422
{"errors":{
  "clinic_address_id":"حقل clinic address id مطلوب.",
  "service_type_id":"حقل نوع الخدمة مطلوب.",
  "date":"حقل date مطلوب."}}
```

### والحجز يحتاجها أيضاً

`POST /api/v1/bookings` (`Api\V1\BookingController::store`) — **موجود وكامل**، لكنه يطلب:

```
doctor_id            required, exists:doctors,id
service_type_id      required, exists:service_types,id
clinic_address_id    required, exists:clinic_addresses,id
scheduled_at         required, date, after:now
session_count        nullable, 1-30
notes                nullable, max:1000
patient_home_address required إذا requires_patient_address
payment_mode         nullable, enum
```

**النتيجة:** الوكيل لا يستطيع رؤية المواعيد ولا الحجز. **السلوك والمهارات سليمة — البيانات ناقصة.**

### المبدأ التصميمي للإصلاح

> **كل مسار في واجهة الوكيل يقبل ما يملكه الوكيل (`slug`, `date`)، ويحلّ المعرّفات داخلياً.**

المعرّفات الرقمية تفاصيل داخلية لا معنى لها في محادثة، وإجبار الوكيل على تمريرها يجعل المهارة هشّة.

### أنواع الخدمات — الأكواد الحقيقية

```
id | code                      | الاسم                   | requires_patient_address
 1 | clinic_appointment        | موعد بالعيادة            | لا
 2 | home_visit                | زيارة منزلية             | نعم  ← عنوان مطلوب
 3 | video_consultation        | استشارة بالفيديو          | لا
 4 | lab_test                  | تحاليل بالمعمل            | لا
 5 | home_lab_test             | تحاليل منزلية             | نعم  ← عنوان مطلوب
 6 | psychiatric_consultation  | استشارة نفسية أونلاين     | لا
```

### الملفات المعنية

```
app/Http/Controllers/Api/V1/SlotController.php      ← يُعدَّل
app/Http/Controllers/Api/Agent/V1/BookingController.php  ← ملف جديد
routes/agent.php                                    ← يُضاف مسار
lang/ar/booking.php                                 ← نصوص الأخطاء

يُعاد استخدامه كما هو (لا تلمسه):
  app/Services/AvailabilityService.php   → slots(), durationMinutes(), assertBookable()
  app/Services/BookingManager.php        → create()
  app/Services/PaymentOptions.php        → defaultMode(), allows()
  app/Models/Doctor, ClinicAddress, ServiceType, Booking
```

---

## ١.١ البحث — أضف المعرّفات

`SearchController` — في تحويل الطبيب، أضف `id` للطبيب، وبدّل `clinics` من أسماء إلى كائنات:

```php
'id'   => $doctor->id,
'clinics' => $doctor->clinics->map(fn ($clinic) => [
    'id'      => $clinic->id,
    'name'    => $clinic->name,
    'addresses' => $clinic->addresses->map(fn ($a) => [
        'id'      => $a->id,
        'label'   => $a->label_ar ?: $a->address_line,
        'city'    => $a->city?->name_ar,
        'primary' => (bool) $a->is_primary,
    ]),
]),
```

**توافق خلفي:** احتفظ بمفتاح `clinics` القديم إن كان مستخدماً في الواجهة، أو أضف `clinic_names` منفصلاً. **لا تكسر الواجهة الحالية.**

**البديل الأبسط — وهو الموصى به:** لا تعدّل البحث إطلاقاً، واعتمد على ١.٢ التي تُرجّع الخيارات مع معرّفاتها. أقل تغييراً وأقل خطراً.

---

## ١.٢ المواعيد — معاملات اختيارية + قائمة الخيارات

**`app/Http/Controllers/Api/V1/SlotController.php`** — استبدل دالة `__invoke`:

```php
public function __invoke(Request $request, Doctor $doctor, AvailabilityService $availability): JsonResponse
{
    $clinicIds = method_exists($doctor->clinics()->getModel(), 'scopeListable')
        ? $doctor->clinics()->listable()->pluck('clinics.id')
        : $doctor->clinics()->where('is_active', true)->pluck('clinics.id');

    abort_unless($doctor->is_active && $clinicIds->isNotEmpty(), 404);

    // كل المعاملات صارت اختيارية: الوكيل يملك الـ slug فقط
    $validated = $request->validate([
        'clinic_address_id' => ['nullable', 'integer', 'exists:clinic_addresses,id'],
        'service_type_id'   => ['nullable', 'integer', 'exists:service_types,id'],
        'date'              => ['nullable', 'date'],
    ]);

    $address = ClinicAddress::query()
        ->with('schedules')
        ->whereIn('clinic_id', $clinicIds)
        ->when($validated['clinic_address_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
        ->where('is_active', true)
        ->orderByDesc('is_primary')
        ->orderBy('id')
        ->first();

    abort_unless($address, 404);

    $serviceType = ServiceType::query()
        ->when(
            $validated['service_type_id'] ?? null,
            fn ($q, $id) => $q->whereKey($id),
            fn ($q) => $q->where('code', 'clinic_appointment')
        )
        ->first()
        ?? ServiceType::query()->where('is_active', true)->orderBy('display_order')->first();

    abort_unless($serviceType, 404);

    $date = $validated['date'] ?? now('Africa/Cairo')->toDateString();
    $doctor->load('availability');

    return response()->json([
        // ما يحتاجه الوكيل ليسأل الزائر: الفروع وأنواع الخدمة، بمعرّفاتها
        'options' => [
            'addresses' => ClinicAddress::query()
                ->with('city')
                ->whereIn('clinic_id', $clinicIds)
                ->where('is_active', true)
                ->orderByDesc('is_primary')
                ->get()
                ->map(fn ($a) => [
                    'id'      => $a->id,
                    'label'   => $a->label_ar ?: $a->address_line,
                    'city'    => $a->city?->name_ar,
                    'primary' => (bool) $a->is_primary,
                ])->values(),

            'service_types' => ServiceType::query()
                ->where('is_active', true)
                ->orderBy('display_order')
                ->get()
                ->map(fn ($s) => [
                    'id'   => $s->id,
                    'code' => $s->code,
                    'name' => $s->name_ar,
                    'requires_patient_address' => (bool) $s->requires_patient_address,
                ])->values(),
        ],

        // الافتراضي المُختار — يمرّره الوكيل في الحجز كما هو
        'resolved' => [
            'doctor_id'         => $doctor->id,
            'clinic_address_id' => $address->id,
            'service_type_id'   => $serviceType->id,
            'date'              => $date,
        ],

        'data' => $availability->slots(
            $doctor, $address, $serviceType, Carbon::parse($date)->startOfDay()
        ),
        'duration_minutes' => $availability->durationMinutes($address, $serviceType),
    ]);
}
```

**`options` ضرورية:** بلاها الوكيل لا يستطيع أن يسأل *«موعد بالعيادة أم زيارة منزلية؟»* ولا *«أنهي فرع؟»* — فيفشل السيناريو في كل الخدمات المتعددة.

---

## ١.٣ الحجز — مسار واجهة الوكيل

**ملف جديد:** `app/Http/Controllers/Api/Agent/V1/BookingController.php`

```php
<?php

namespace App\Http\Controllers\Api\Agent\V1;

use App\Enums\PaymentMode;
use App\Http\Controllers\Controller;
use App\Models\ClinicAddress;
use App\Models\Doctor;
use App\Models\ServiceType;
use App\Services\AgentCustomerAuthenticator;
use App\Services\BookingManager;
use App\Services\PaymentOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private BookingManager $bookings,
        private PaymentOptions $payments,
    ) {}

    public function store(Request $request, AgentCustomerAuthenticator $auth): JsonResponse
    {
        $validated = $request->validate([
            'doctor_slug'          => ['required', 'string', 'exists:doctors,slug'],
            'scheduled_at'         => ['required', 'date', 'after:now'],
            'clinic_address_id'    => ['nullable', 'integer', 'exists:clinic_addresses,id'],
            'service_type_id'      => ['nullable', 'integer', 'exists:service_types,id'],
            'session_count'        => ['nullable', 'integer', 'min:1', 'max:30'],
            'notes'                => ['nullable', 'string', 'max:1000'],
            'patient_home_address' => ['nullable', 'string', 'max:255'],
            'payment_mode'         => ['nullable', Rule::enum(PaymentMode::class)],
        ]);

        $customer = $auth->require($request);          // 401 إن لم يسجّل الدخول

        $doctor = Doctor::where('slug', $validated['doctor_slug'])->firstOrFail();
        abort_unless($doctor->is_active, 404);

        $clinicIds = method_exists($doctor->clinics()->getModel(), 'scopeListable')
            ? $doctor->clinics()->listable()->pluck('clinics.id')
            : $doctor->clinics()->where('is_active', true)->pluck('clinics.id');
        abort_if($clinicIds->isEmpty(), 404);

        $address = ClinicAddress::query()
            ->with('clinic')
            ->whereIn('clinic_id', $clinicIds)
            ->when($validated['clinic_address_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->where('is_active', true)
            ->orderByDesc('is_primary')->orderBy('id')->first();
        abort_unless($address, 404);

        $serviceType = ServiceType::query()
            ->when($validated['service_type_id'] ?? null, fn ($q, $id) => $q->whereKey($id))
            ->first()
            ?? ServiceType::query()->where('is_active', true)->orderBy('display_order')->first();
        abort_unless($serviceType, 404);

        // العنوان شرطي على نوع الخدمة — يُفحص بعد الحلّ لا قبله،
        // لأن الوكيل قد لا يمرّر service_type_id أصلاً
        if ($serviceType->requires_patient_address && blank($validated['patient_home_address'] ?? null)) {
            throw ValidationException::withMessages([
                'patient_home_address' => __('booking.home_address_required'),
            ]);
        }

        $mode = isset($validated['payment_mode'])
            ? PaymentMode::from($validated['payment_mode'])
            : $this->payments->defaultMode($address->clinic, $serviceType);

        if (! $this->payments->allows($address->clinic, $mode, $serviceType)) {
            throw ValidationException::withMessages([
                'payment_mode' => __('booking.payment_not_offered'),
            ]);
        }

        try {
            $booking = $this->bookings->create([
                'patient_id'           => $customer->id,
                'clinic_id'            => $address->clinic_id,
                'doctor_id'            => $doctor->id,
                'clinic_address_id'    => $address->id,
                'service_type_id'      => $serviceType->id,
                'scheduled_at'         => $validated['scheduled_at'],
                'session_count'        => $validated['session_count'] ?? 1,
                'notes'                => $validated['notes'] ?? null,
                'patient_home_address' => $validated['patient_home_address'] ?? null,
                'payment_mode'         => $mode,
            ], $customer);
        } catch (\DomainException $e) {
            throw ValidationException::withMessages(['scheduled_at' => __($e->getMessage())]);
        }

        return response()->json([
            'ok' => true,
            'booking' => [
                'reference' => (string) $booking->id,
                'status'    => $booking->status?->value ?? (string) $booking->status,
                'doctor'    => $doctor->name,
                'specialty' => $doctor->specialty?->name_ar,
                'clinic'    => $address->clinic?->name,
                'address'   => $address->label_ar ?: $address->address_line,
                'service'   => $serviceType->name_ar,
                'date'      => $booking->scheduled_at->toDateString(),
                'time'      => $booking->scheduled_at->format('H:i'),
                'fee'       => (string) $booking->fee,
                'currency'  => 'EGP',
                'payment'   => $mode->value,
            ],
        ], 201);
    }
}
```

**`routes/agent.php`** — أضف داخل مجموعة `throttle:10,1`:

```php
Route::post('bookings', [BookingController::class, 'store'])->name('bookings.store');
```
واستورد: `use App\Http\Controllers\Api\Agent\V1\BookingController;`

**`lang/ar/booking.php`:**

```php
return [
    'slot_taken'            => 'الموعد ده اتحجز في اللحظة، تحب تختار موعد تاني؟',
    'doctor_closed'         => 'الطبيب مش متاح في اليوم ده.',
    'not_verified'          => 'حسابك محتاج تأكيد رقم الموبايل الأول.',
    'duplicate'             => 'فيه حجز حالي لنفس الطبيب في نفس اليوم.',
    'payment_not_offered'   => 'طريقة الدفع دي مش متاحة للعيادة.',
    'home_address_required' => 'الخدمة دي محتاجة عنوانك بالتفصيل.',
];
```

---

## ١.٤ النماذج — الحقول حسب نوع الخدمة

النافذة تعرض نموذجاً بالحقول. **الحقول تتغيّر بتغيّر نوع الخدمة.** حدّث `config('agent.forms')` (أو أي مصدر تُبنى منه النماذج):

```php
'booking' => [
    'title'  => 'بيانات الحجز',
    'submit' => 'أكّد الحجز',
    'fields' => [
        ['name' => 'patient_name',   'label' => 'الاسم',           'type' => 'text',  'required' => true],
        ['name' => 'patient_phone',  'label' => 'رقم الموبايل',     'type' => 'tel',   'required' => true],
        ['name' => 'patient_home_address', 'label' => 'العنوان بالتفصيل', 'type' => 'text', 'required_when' => 'requires_patient_address'],
        ['name' => 'notes',          'label' => 'ملاحظات (اختياري)', 'type' => 'textarea', 'required' => false],
    ],
],
```

**`required_when`** يعني: أظهر الحقل كمطلوب **فقط** حين يكون نوع الخدمة المُختار `requires_patient_address = true`. النافذة تعرف ذلك من `options.service_types` الراجعة من ١.٢، فترسل علمها مع النموذج.

**النتيجة للزائر:** «موعد بالعيادة» → الاسم والموبايل. «زيارة منزلية» → الاسم والموبايل **والعنوان**.

---

## ١.٥ اختبارات القبول

```bash
K="$HERMES_AGENT_KEY"; H="-H X-Hermes-Key:$K -H X-Locale:ar"
BASE="https://eg.hakeem.com.sa/api/agent/v1"

# ١. المواعيد تعمل بلا معرّفات (كانت 422)
curl $H "$BASE/doctors/dr-george-kamel/slots"
→ 200 · فيه options + resolved + data

# ٢. الخيارات تحمل المعرّفات والعلم الشرطي
→ options.service_types[i].id و requires_patient_address
→ options.addresses[i].id

# ٣. الحجز بلا تسجيل دخول يُرفض
curl -X POST $H "$BASE/bookings" -d '{"doctor_slug":"dr-george-kamel","scheduled_at":"..."}'
→ 401

# ٤. الحجز بحساب ينجح
→ 201 · booking.reference موجود

# ٥. زيارة منزلية بلا عنوان
-d '{"service_type_id":2,...}'
→ 422 · patient_home_address

# ٦. زيارة منزلية مع عنوان
→ 201

# ٧. موعد محجوز مسبقاً
→ 422 · slot_taken  (وليس 500)
```

**قاعدة:** كل خطأ يعود **422 برسالة عربية مفهومة**، لا 500 بمكدس أخطاء. ماهر يشرح الرسالة للزائر.

---

# الجزء ٢ — كلمة المرور: التحقق قبل الإرسال

## الثغرة

`PasswordResetService::send()`:

```php
if (! $user || ! $user->is_active) {
    return $identifier->channel;   // ← يقول «تم» ولم يُرسل شيئاً
}
```

رقم غير مسجّل → الرد `ok:true` → الزائر ينتظر كوداً **لن يصل**، والوكيل لا يعرف لماذا.

## لماذا الصمت صحيح في صفحة الدخول وخطأ في واجهة الوكيل

- **صفحة الدخول العامة:** الصمت يمنع حصر الحسابات (enumeration). صحيح.
- **واجهة الوكيل:** المفتاح محمي بـ `X-Hermes-Key`، والمسار محدود المعدل، والغرض كله أن يعرف الوكيل **ماذا يقول للزائر**. فالصمت هنا يُنتج طريقاً مسدوداً.

## الإصلاح — في واجهة الوكيل فقط

`app/Http/Controllers/Api/Agent/V1/CustomerController.php::forgotPassword` — أضف **قبل** الـ throttle:

```php
$user = $identifier->findUser();

if (! $user || ! $user->is_active) {
    throw ValidationException::withMessages([
        'identifier' => __('auth.identifier_not_found'),
    ]);
}
```

**`lang/ar/auth.php`:**

```php
'identifier_not_found' => 'الرقم أو الإيميل ده مش مسجّل عندنا.',
```

**التحقق المُقاس فعلاً بعد الإصلاح:**

```
رقم غير مسجّل  → 422 · "الرقم أو الإيميل ده مش مسجّل عندنا."
رقم مسجّل      → 200 · {"ok":true,"channel":"phone",...}
```

---

# الجزء ٣ — سجلات المحادثة

## الحالة — فحص فعلي

**٤٩ جدولاً في قاعدة البيانات، ولا جدول للمحادثات.**

| المخزن | مدة البقاء | مرئي في اللوحة؟ |
|---|---|---|
| كاش Laravel | ساعتان (آخر 20 رسالة) | ❌ |
| `localStorage` | 110 دقائق | ❌ |
| قاعدة البيانات | **لا شيء** | ❌ |

## الهدف

- سجل الزائر **يبقى ٢٤ ساعة** في النافذة
- **اللوحة تكشف كل المحادثات** بلا حد زمني

## ٣.١ جدولان

```php
Schema::create('agent_conversations', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('patient_id')->nullable()->constrained('users')->nullOnDelete();
    $table->string('channel', 16)->default('web');   // web | whatsapp | telegram
    $table->string('locale', 8)->default('ar');
    $table->string('visitor_name')->nullable();
    $table->ipAddress('ip')->nullable();
    $table->unsignedTinyInteger('satisfaction')->nullable();
    $table->timestamp('started_at');
    $table->timestamp('last_message_at')->nullable();
    $table->unsignedInteger('message_count')->default(0);
    $table->timestamps();
    $table->index('last_message_at');
    $table->index(['channel', 'last_message_at']);
});

Schema::create('agent_messages', function (Blueprint $table) {
    $table->id();
    $table->uuid('conversation_id');
    $table->string('role', 16);                 // user | assistant | system
    $table->text('body');
    $table->jsonb('actions')->nullable();       // الأزرار المعروضة
    $table->jsonb('results')->nullable();
    $table->jsonb('forms')->nullable();         // النماذج المعروضة
    $table->unsignedInteger('latency_ms')->nullable();
    $table->timestamps();
    $table->foreign('conversation_id')
        ->references('id')->on('agent_conversations')->cascadeOnDelete();
    $table->index(['conversation_id', 'id']);
    $table->index('created_at');
});
```

**لماذا `jsonb` للأزرار؟** لتعرف أي الأزرار تُعرض ويُضغط عليها فعلاً — بهذا تحسّن الرحلة بالبيانات لا بالحدس.

## ٣.٢ الحفظ — نقطتان حرجتان

في `SiteAgent::viaHermes()`، بجانب `Cache::put` **لا بدلاً منها**:

```php
$conversation = AgentConversation::firstOrCreate(
    ['id' => $conversationId],
    ['patient_id' => $user?->id, 'channel' => 'web', 'locale' => app()->getLocale(),
     'visitor_name' => $user?->name, 'ip' => $request->ip(), 'started_at' => now()],
);

$conversation->messages()->create(['role' => 'user', 'body' => $this->redact($text)]);

// بعد رد الوكيل
$conversation->messages()->create([
    'role' => 'assistant', 'body' => $completed['reply'],
    'actions' => $completed['actions'] ?? null,
    'results' => $completed['results'] ?? null,
    'forms'   => $completed['forms'] ?? null,
    'latency_ms' => $elapsedMs,
]);

$conversation->update([
    'last_message_at' => now(),
    'message_count'   => $conversation->messages()->count(),
]);
```

**١. الحفظ داخل `try/catch` منفصل.** فشل السجل **يجب ألا يُعطّل الشات أبداً** — الزائر أهم من السجل.

**٢. لا تسجّل كلمات المرور:**

```php
private function redact(string $text): string
{
    if (preg_match('/(?:كلمة\s*المرور|password|passwd)\s*[:=]\s*\S+/iu', $text)) {
        return '(بيانات دخول — لم تُسجَّل)';
    }
    return $text;
}
```

لمنصة طبية هذا **ليس تفصيلاً تجميلياً**، بل شرط امتثال.

## ٣.٣ سجل الزائر يبقى ٢٤ ساعة

في `SiteAgent`:

```php
Cache::put($this->cacheKey($conversationId), [
    'history' => array_slice($history, -40),   // كان 20
], now()->addHours(24));                        // كان ساعتين
```

**ونظّف `localStorage` في النافذة** — حالياً 110 دقائق. ارفعها إلى ٢٤ ساعة لتتطابق.

## ٣.٤ صفحة اللوحة

**قائمة المحادثات:** التاريخ والوقت · القناة · الزائر · عدد الرسائل · مقتطف آخر رسالة
**فلترة:** القناة · التاريخ · بحث نصي في النصوص

**صفحة المحادثة:** كل الرسائل بالترتيب · الأزرار المعروضة مع كل رد · النماذج · زمن الاستجابة · رابط لحساب الزائر

## ٣.٥ سياسة الاحتفاظ — قرار لازم قبل التشغيل

```php
// app/Console/Commands/PruneAgentConversations.php — شغّله يومياً
$days = config('agent.retention_days', 90);
AgentConversation::where('last_message_at', '<', now()->subDays($days))
    ->chunkById(200, fn ($rows) => $rows->each->delete());
```

**نصوص المرضى تُخزَّن للأبد افتراضياً.** حدّد المدة **قبل** التشغيل لا بعده، وحدّث سياسة الخصوصية لتذكر أن المحادثات تُسجَّل للمتابعة والجودة.

---

# الجزء ٤ — مرجع الحقول والمتغيرات

## ٤.١ متغيرات البيئة

### `/var/hakeem/.env` (Laravel)

```bash
APP_DEBUG=false                              # ← كان true على موقع عام! يسرب مكدس الأخطاء

HAKEEM_BASE_URL=https://eg.hakeem.com.sa
HERMES_AGENT_KEY=<مفتاح واجهة الوكيل>          # يُرسل كـ X-Hermes-Key

# في وضع multiplex صار المسار تحت /p/<profile> والمنفذ 8642 (كان 8650)
HERMES_CHAT_URL=http://127.0.0.1:8642/p/hakeem/v1/chat/completions
HERMES_CHAT_MODEL=hakeem-agent
HERMES_CHAT_KEY=<نفس HERMES_AGENT_KEY>

# الصوت
TTS_PROVIDER=edge
EDGE_TTS_VOICE=ar-EG-ShakirNeural
ELEVENLABS_API_KEY=
ELEVENLABS_VOICE_ID=
ELEVENLABS_MODEL_ID=eleven_multilingual_v2
STT_LANGUAGE=ar
```

### `/opt/tts/tts.env` (محرّك الصوت)

```bash
TTS_PROVIDER=edge                  # edge | elevenlabs
EDGE_TTS_VOICE=ar-EG-ShakirNeural  # ذكري — مطابق لشخصية «ماهر»
EDGE_TTS_VOICE_MALE=ar-EG-ShakirNeural
ELEVENLABS_API_KEY=
ELEVENLABS_VOICE_ID=
```

**الأصوات المصرية المجانية:** `ar-EG-SalmaNeural` (أنثوي) · `ar-EG-ShakirNeural` (ذكري)

### بروفايل الوكيل

```bash
HAKEEM_BASE_URL=https://eg.hakeem.com.sa
HERMES_AGENT_KEY=<مفتاح>
OPENROUTER_API_KEY=<مفتاح>
TELEGRAM_BOT_TOKEN=<توكن البوت>
```

## ٤.٢ عناوين المسارات

### واجهة الوكيل — تحتاج `X-Hermes-Key`

```
GET  /api/agent/v1/                              ← manifest
GET  /api/agent/v1/help
GET  /api/agent/v1/suggestions?q=
GET  /api/agent/v1/search?type=doctors|clinics|services&q=&city=
GET  /api/agent/v1/doctors/{slug}
GET  /api/agent/v1/doctors/{slug}/slots          ← يُعدَّل (١.٢)
GET  /api/agent/v1/clinics/{slug}
GET  /api/agent/v1/offers
GET  /api/agent/v1/campaigns
GET  /api/agent/v1/reference/geography
GET  /api/agent/v1/reference/specialties
GET  /api/agent/v1/reference/service-types

POST /api/agent/v1/customers                     ← تسجيل
POST /api/agent/v1/customers/session             ← دخول → توكن
POST /api/agent/v1/customers/password/forgot     ← يُعدَّل (٢)
POST /api/agent/v1/customers/password/reset
POST /api/agent/v1/support/tickets
POST /api/agent/v1/bookings                      ← جديد (١.٣)

GET  /api/agent/v1/customers/me                  ← يحتاج Bearer
PUT  /api/agent/v1/customers/me
GET  /api/agent/v1/customers/bookings
```

### الترويسات

```
X-Hermes-Key: <HERMES_AGENT_KEY>     ← إلزامي على كل نداء
X-Locale: ar
Authorization: Bearer <token>        ← بعد تسجيل الدخول فقط
```

## ٤.٣ شكل رد النافذة

```json
{
  "conversation_id": "uuid",
  "reply": "النص المنظّف — بلا روابط مكشوفة ولا علامات",
  "intent": "hermes",
  "source": "hermes",
  "results": { "doctors": [] },
  "actions": [
    { "type": "link",  "label": "احجز مع د. جورج", "url": "https://..." },
    { "type": "reply", "label": "٩:٢٠ ص", "value": "٩:٢٠ ص" }
  ],
  "cards": [],
  "forms": [
    { "form": "booking",
      "fields": [
        { "name": "patient_name", "label": "الاسم", "type": "text", "required": true },
        { "name": "patient_home_address", "label": "العنوان", "type": "text", "required": false }
      ] }
  ]
}
```

**ثلاثة عيوب أُصلحت في السيرفر — تأكد أن نسختك لا تعيدها:**

```
١. ترتيب: formsFromText يجب أن يعمل على الرد الخام، قبل cleanReplyText
   (وإلا يمسح التنظيف العلامة فتجد الدالة لا شيء)

٢. SiteAgent::payload() كان يبني رداً جديداً ويُسقط 'forms' بصمت
   (النموذج يُستخرج ثم يُرمى — أخطرها لأنه صامت)

٣. actionsFromText كان يحوّل [[form:register]] إلى زِر
   (الصيغة الصحيحة: '/\[\[(?!form:)([^\]\[]{1,60})\]\]/u')
```

---

# أولويات التنفيذ

| # | العمل | يحجب؟ |
|---|---|---|
| **١** | الحجز: البحث + المواعيد + مسار الحجز + حقول الخدمة | **نعم — الوظيفة الأساسية معطّلة** |
| ٢ | كلمة المرور: التحقق قبل الإرسال | لا — لكنه يُنتج وعوداً كاذبة |
| ٣ | سجلات المحادثة | لا — لكنك لا ترى ما يقوله ماهر للناس |
| ٤ | `APP_DEBUG=false` | **نعم أمنياً** — تسريب مكدس أخطاء على موقع عام |
| ٥ | `trustProxies` في `bootstrap/app.php` | لا — لكن الروابط تخرج `http://` فتُحجب |

**ابدأ بالحجز** — بدونه ماهر يعد بالحجز ولا يحجز، وهذا أسوأ من عدم الوعد.