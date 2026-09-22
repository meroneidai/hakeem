<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class SearchQuery
{
    /**
     * Split a search box into AND-tokens so "سارة باطنة" matches both words.
     *
     * @return list<string>
     */
    public static function tokens(?string $term): array
    {
        $term = trim((string) $term);

        if ($term === '') {
            return [];
        }

        $parts = preg_split('/\s+/u', $term) ?: [];

        return array_values(array_unique(array_filter(
            array_map(static fn (string $part): string => trim($part), $parts),
            static fn (string $part): bool => $part !== '',
        )));
    }

    /**
     * Apply every token with AND; the callback adds OR-columns for one token.
     *
     * @param  callable(Builder, string): void  $match
     */
    public static function constrain(Builder $query, ?string $term, callable $match): Builder
    {
        foreach (self::tokens($term) as $token) {
            $query->where(fn (Builder $inner) => $match($inner, $token));
        }

        return $query;
    }
}
