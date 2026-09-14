<?php

namespace App\Enums;

/**
 * Stored as ISO-8601 day numbers (1 = Monday … 7 = Sunday) so the values line up
 * with Carbon's `isoWeekday()`. Display order starts on Saturday because that is
 * the first working day of the Egyptian week.
 */
enum DayOfWeek: int
{
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;
    case Sunday = 7;

    public function labelAr(): string
    {
        return match ($this) {
            self::Saturday => 'السبت',
            self::Sunday => 'الأحد',
            self::Monday => 'الاثنين',
            self::Tuesday => 'الثلاثاء',
            self::Wednesday => 'الأربعاء',
            self::Thursday => 'الخميس',
            self::Friday => 'الجمعة',
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::Saturday => 'Saturday',
            self::Sunday => 'Sunday',
            self::Monday => 'Monday',
            self::Tuesday => 'Tuesday',
            self::Wednesday => 'Wednesday',
            self::Thursday => 'Thursday',
            self::Friday => 'Friday',
        };
    }

    public function label(): string
    {
        return app()->getLocale() === 'ar' ? $this->labelAr() : $this->labelEn();
    }

    /**
     * Cases ordered for display, Saturday first.
     *
     * @return list<self>
     */
    public static function weekOrder(): array
    {
        return [
            self::Saturday,
            self::Sunday,
            self::Monday,
            self::Tuesday,
            self::Wednesday,
            self::Thursday,
            self::Friday,
        ];
    }

    /**
     * Days a clinic is open by default when it first sets up a branch.
     *
     * @return list<self>
     */
    public static function defaultWorkingDays(): array
    {
        return [self::Saturday, self::Sunday, self::Monday, self::Tuesday, self::Wednesday, self::Thursday];
    }
}
