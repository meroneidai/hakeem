<?php

namespace App\Support;

class ServiceDuration
{
    /**
     * @return list<int>
     */
    public static function presets(): array
    {
        return [15, 30, 45, 60, 120];
    }

    /**
     * @return array<int, string>
     */
    public static function options(?int $current = null): array
    {
        $minutes = self::presets();

        if ($current && ! in_array($current, $minutes, true)) {
            $minutes[] = $current;
            sort($minutes);
        }

        return collect($minutes)
            ->mapWithKeys(fn (int $value) => [$value => __('booking.duration_minutes', ['minutes' => $value])])
            ->all();
    }

    public static function resolve(?int $override, ?int $fallback = null): int
    {
        return max(5, (int) ($override ?: $fallback ?: 30));
    }
}
