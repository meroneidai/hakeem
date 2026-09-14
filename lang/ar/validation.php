<?php

/*
 * Arabic messages for the rules this app actually uses. Missing keys fall back
 * to the English messages via APP_FALLBACK_LOCALE.
 */

return [
    'required' => 'حقل :attribute مطلوب.',
    'required_if' => 'حقل :attribute مطلوب عندما تكون قيمة :other هي :value.',
    'required_unless' => 'حقل :attribute مطلوب إلا إذا كانت قيمة :other ضمن :values.',
    'confirmed' => 'تأكيد :attribute غير مطابق.',
    'email' => 'قيمة :attribute يجب أن تكون بريدًا إلكترونيًا صحيحًا.',
    'unique' => 'قيمة :attribute مستخدمة بالفعل.',
    'exists' => 'قيمة :attribute المختارة غير صحيحة.',
    'in' => 'قيمة :attribute المختارة غير صحيحة.',
    'boolean' => 'حقل :attribute يجب أن يكون صحيحًا أو خطأ.',
    'integer' => 'حقل :attribute يجب أن يكون رقمًا صحيحًا.',
    'numeric' => 'حقل :attribute يجب أن يكون رقمًا.',
    'date' => 'حقل :attribute ليس تاريخًا صحيحًا.',
    'after' => 'حقل :attribute يجب أن يكون تاريخًا بعد :date.',
    'after_or_equal' => 'حقل :attribute يجب أن يكون تاريخًا بعد أو يساوي :date.',
    'alpha_dash' => 'حقل :attribute يجب أن يحتوي على حروف وأرقام وشرطات فقط.',
    'regex' => 'صيغة حقل :attribute غير صحيحة.',
    'array' => 'حقل :attribute يجب أن يكون قائمة.',

    'min' => [
        'string' => 'حقل :attribute يجب ألا يقل عن :min حرفًا.',
        'numeric' => 'حقل :attribute يجب ألا يقل عن :min.',
        'array' => 'يجب اختيار :min عنصر على الأقل في :attribute.',
    ],
    'max' => [
        'string' => 'حقل :attribute يجب ألا يزيد عن :max حرفًا.',
        'numeric' => 'حقل :attribute يجب ألا يزيد عن :max.',
        'array' => 'يجب ألا يزيد :attribute عن :max عنصر.',
    ],

    'password' => [
        'letters' => 'كلمة المرور يجب أن تحتوي على حرف واحد على الأقل.',
        'mixed' => 'كلمة المرور يجب أن تحتوي على حرف كبير وحرف صغير.',
        'numbers' => 'كلمة المرور يجب أن تحتوي على رقم واحد على الأقل.',
        'symbols' => 'كلمة المرور يجب أن تحتوي على رمز واحد على الأقل.',
    ],

    'attributes' => [
        'name' => 'الاسم',
        'name_ar' => 'الاسم بالعربية',
        'name_en' => 'الاسم بالإنجليزية',
        'title_ar' => 'العنوان بالعربية',
        'title_en' => 'العنوان بالإنجليزية',
        'phone' => 'رقم الهاتف',
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'slug' => 'المُعرّف',
        'governorate_id' => 'المحافظة',
        'city_id' => 'المدينة',
        'specialty_id' => 'التخصص',
        'service_type_id' => 'نوع الخدمة',
        'subscription_plan_id' => 'خطة الاشتراك',
        'category' => 'الفئة',
        'code' => 'الكود',
        'discount_type' => 'نوع الخصم',
        'discount_value' => 'قيمة الخصم',
        'discount_details' => 'تفاصيل العرض',
        'monthly_price' => 'السعر الشهري',
        'yearly_price' => 'السعر السنوي',
        'booking_cap' => 'حد الحجوزات',
        'starts_at' => 'تاريخ البداية',
        'ends_at' => 'تاريخ النهاية',
        'roles' => 'الأدوار',
        'status' => 'الحالة',
        'priority' => 'الأولوية',
        'body' => 'نص الرسالة',
        'allowed_modes' => 'أنماط الدفع',
        'default_mode' => 'النمط الافتراضي',
        'gateway' => 'بوابة الدفع',
        'sitemap_priority' => 'أولوية الخريطة',
        'preferred_language' => 'اللغة',
        'display_order' => 'ترتيب العرض',
    ],
];
