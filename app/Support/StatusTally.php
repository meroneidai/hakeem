<?php

namespace App\Support;

use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StatusTally
{
    /**
     * Count rows grouped by a backed-enum status column.
     *
     * @param  Builder<Model>  $query
     * @param  list<BackedEnum>  $cases
     * @return array<string, int>
     */
    public static function of(Builder $query, array $cases): array
    {
        $counts = [];

        foreach ($cases as $case) {
            $counts[$case->value] = 0;
        }

        $rows = (clone $query)
            ->reorder()
            ->setEagerLoads([])
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->get();

        foreach ($rows as $row) {
            $key = $row->status instanceof BackedEnum
                ? $row->status->value
                : (string) $row->status;

            if (array_key_exists($key, $counts)) {
                $counts[$key] = (int) $row->total;
            }
        }

        return $counts;
    }
}
