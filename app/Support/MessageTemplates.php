<?php

namespace App\Support;

class MessageTemplates
{
    public function __construct(private Settings $settings) {}

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            'messages.promo_sms' => 'عرض جديد على حكيم: {title}',
            'messages.promo_push' => 'عرض طبي جديد بالقرب منك',
            'messages.promo_email_subject' => 'عروض حكيم هذا الأسبوع',
            'messages.promo_email_body' => 'اكتشف العروض الجارية على الكشف والتحاليل من عيادات موثّقة. {title}',
            'messages.sms_booking_created' => 'تم إرسال طلب حجزك على حكيم يا {name}. العيادة ستؤكده قريبًا.',
            'messages.sms_booking_confirmed' => 'تم تأكيد موعدك في {clinic} يوم {date}. راجع التفاصيل في حكيم.',
            'messages.sms_booking_cancelled' => 'تم إلغاء موعدك في {clinic}. يمكنك إعادة الحجز من حكيم.',
            'messages.sms_booking_reminder' => 'تذكير: لديك موعد اليوم في {clinic} الساعة {date}.',
            'messages.sms_booking_completed' => 'اكتملت زيارتك في {clinic}. شكرًا لاستخدامك حكيم.',
            'messages.sms_evaluation_booked' => 'تم حجز جلسة تقييم في {clinic} يوم {date}.',
            'messages.sms_lab_order_created' => 'تم استلام طلب التحاليل {reference}. المعمل سيؤكده قريبًا.',
            'messages.sms_lab_order_confirmed' => 'تم تأكيد طلب التحاليل {reference} في {clinic} يوم {date}.',
            'messages.sms_lab_order_completed' => 'نتائج طلب التحاليل {reference} جاهزة للمتابعة في حكيم.',
            'messages.sms_lab_order_cancelled' => 'تم إلغاء طلب التحاليل {reference}.',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::defaults());
    }

    /**
     * @return array<string, string>
     */
    public static function eventMap(): array
    {
        return [
            'booking_created' => 'messages.sms_booking_created',
            'booking_confirmed' => 'messages.sms_booking_confirmed',
            'booking_cancelled' => 'messages.sms_booking_cancelled',
            'booking_reminder' => 'messages.sms_booking_reminder',
            'booking_completed' => 'messages.sms_booking_completed',
            'booking_rescheduled' => 'messages.sms_booking_confirmed',
            'evaluation_booked' => 'messages.sms_evaluation_booked',
            'lab_order_created' => 'messages.sms_lab_order_created',
            'lab_order_confirmed' => 'messages.sms_lab_order_confirmed',
            'lab_order_completed' => 'messages.sms_lab_order_completed',
            'lab_order_cancelled' => 'messages.sms_lab_order_cancelled',
            'promotion_published' => 'messages.promo_sms',
        ];
    }

    public function value(string $key): string
    {
        $stored = $this->settings->get($key);

        if (filled($stored)) {
            return (string) $stored;
        }

        return self::defaults()[$key] ?? '';
    }

    /**
     * @return array<string, string>
     */
    public function allResolved(): array
    {
        $values = [];

        foreach (self::defaults() as $key => $default) {
            $values[$key] = $this->value($key) ?: $default;
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $replacements
     */
    public function render(string $key, array $replacements = []): string
    {
        $template = $this->value($key);

        foreach ($replacements as $name => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $template = str_replace('{'.$name.'}', (string) $value, $template);
        }

        return $template;
    }

    /**
     * @param  array<string, mixed>  $replacements
     */
    public function forEvent(string $event, array $replacements = []): ?string
    {
        $key = self::eventMap()[$event] ?? null;

        if ($key === null) {
            return null;
        }

        return $this->render($key, $replacements);
    }
}
