<?php

namespace App\Enums;

enum ClinicModule: string
{
    case Appointments = 'appointments';
    case PhysicalTherapy = 'physical_therapy';
    case Labs = 'labs';
    case Promotions = 'promotions';
    case Dental = 'dental';
    case Cosmetic = 'cosmetic';
    case Massage = 'massage';

    /**
     * Always-on core. Clinics cannot hide the booking queue.
     *
     * @return list<self>
     */
    public static function required(): array
    {
        return [self::Appointments];
    }

    /**
     * Chosen during registration; unused modules disappear from the clinic dashboard.
     *
     * @return list<self>
     */
    public static function selectable(): array
    {
        return [
            self::PhysicalTherapy,
            self::Labs,
            self::Promotions,
            self::Dental,
            self::Cosmetic,
            self::Massage,
        ];
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::Appointments => 'حجوزات العيادة',
            self::PhysicalTherapy => 'العلاج الطبيعي والتأهيل',
            self::Labs => 'خدمات التحاليل',
            self::Promotions => 'العروض الترويجية',
            self::Dental => 'خدمات الأسنان',
            self::Cosmetic => 'التجميل غير الجراحي',
            self::Massage => 'المساج والعافية',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Appointments => 'Clinic appointments',
            self::PhysicalTherapy => 'Physiotherapy & rehab',
            self::Labs => 'Laboratory services',
            self::Promotions => 'Promotional offers',
            self::Dental => 'Dental services',
            self::Cosmetic => 'Cosmetic treatments',
            self::Massage => 'Massage & wellness',
        };
    }

    public function hintAr(): string
    {
        return match ($this) {
            self::Appointments => 'مواعيد الكشف والمتابعة في العيادة.',
            self::PhysicalTherapy => 'جلسات علاج طبيعي وتأهيل وظيفي ورياضي. أول زيارة تقييم إلزامي في نفس العيادة قبل أي جلسة لاحقة، ويمكن شراء جلسة أو أكثر.',
            self::Labs => 'فحوصات وباقات من كتالوج حكيم، سلة، حجز سحب في العيادة أو المنزل، وتأكيد من الاستقبال.',
            self::Promotions => 'عروض بخصم واضح (أسنان، تجميل، تحاليل، علاج طبيعي) يمكن للمريض حجزها مباشرة.',
            self::Dental => 'تنظيف، تقويم، زراعة وتجميل أسنان كخدمات مسعّرة بعدد الجلسات عند الحاجة.',
            self::Cosmetic => 'فيلر، بوتكس، ليزر وعناية بالبشرة — كل بند بسعر ومدة واضحين.',
            self::Massage => 'مساج علاجي أو استرخاء وباقات جلسات يمكن شراؤها مسبقاً.',
        };
    }

    public function hintEn(): string
    {
        return match ($this) {
            self::Appointments => 'In-clinic visits and follow-ups.',
            self::PhysicalTherapy => 'Physiotherapy, occupational and sports rehab. The first visit at this clinic is an evaluation; later sessions cannot be accepted without it. Patients may buy one or more sessions.',
            self::Labs => 'Tests and packages from the Hakeem catalog, cart checkout, clinic or home collection, reception confirmation.',
            self::Promotions => 'Priced offers (dental, cosmetic, labs, physio) that patients can book.',
            self::Dental => 'Cleaning, orthodontics, implants and cosmetic dentistry.',
            self::Cosmetic => 'Fillers, Botox, laser and skin care with a clear price and duration.',
            self::Massage => 'Therapeutic or relaxation massage, including session packs.',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'en' ? $this->labelEn() : $this->labelAr();
    }

    public function hint(): string
    {
        return app()->getLocale() === 'en' ? $this->hintEn() : $this->hintAr();
    }

    /**
     * Always includes appointments. Unknown values are dropped.
     *
     * @param  list<string|self>|null  $values
     * @return list<string>
     */
    public static function normalize(?array $values): array
    {
        $selected = collect($values ?? [])
            ->map(fn (self|string $value) => $value instanceof self ? $value->value : $value)
            ->filter(fn (string $value) => self::tryFrom($value) !== null)
            ->unique()
            ->values();

        foreach (self::required() as $required) {
            if (! $selected->contains($required->value)) {
                $selected->prepend($required->value);
            }
        }

        return $selected->unique()->values()->all();
    }

    public static function forServiceType(string $code): self
    {
        return match ($code) {
            ServiceTypeCode::LabTest->value, ServiceTypeCode::HomeLabTest->value => self::Labs,
            ServiceTypeCode::PhysicalTherapy->value, ServiceTypeCode::OccupationalTherapy->value => self::PhysicalTherapy,
            default => self::Appointments,
        };
    }
}
