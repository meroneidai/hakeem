<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UniqueSlug
{
    /**
     * Build a URL-safe unique slug. Arabic-only names often slug to empty, so
     * callers should prefer an English source and pass a fallback.
     */
    public static function for(string $source, string $table, string $column = 'slug', ?int $ignoreId = null): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while (static::exists($table, $column, $slug, $ignoreId)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private static function exists(string $table, string $column, string $slug, ?int $ignoreId): bool
    {
        return DB::table($table)
            ->where($column, $slug)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->exists();
    }
}
