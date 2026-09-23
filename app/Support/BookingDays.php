<?php

namespace App\Support;

class BookingDays
{
    /**
     * @return list<array{value: string, weekday: string, day: string, month: string, today: bool}>
     */
    public static function upcoming(int $count = 14): array
    {
        $today = now()->timezone(config('hakeem.display_timezone'))->startOfDay();

        return collect(range(0, max(1, $count) - 1))->map(function (int $offset) use ($today) {
            $day = $today->copy()->addDays($offset);

            return [
                'value' => $day->toDateString(),
                'weekday' => $day->translatedFormat('D'),
                'day' => $day->format('j'),
                'month' => $day->translatedFormat('M'),
                'today' => $offset === 0,
            ];
        })->all();
    }
}
