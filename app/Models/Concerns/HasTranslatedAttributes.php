<?php

namespace App\Models\Concerns;

/**
 * Resolves `*_ar` / `*_en` column pairs against the active locale,
 * falling back to Arabic (the platform's primary language) when empty.
 */
trait HasTranslatedAttributes
{
    public function translated(string $field): ?string
    {
        $locale = app()->getLocale() === 'en' ? 'en' : 'ar';
        $fallback = $locale === 'en' ? 'ar' : 'en';

        return filled($this->{"{$field}_{$locale}"} ?? null)
            ? $this->{"{$field}_{$locale}"}
            : ($this->{"{$field}_{$fallback}"} ?? null);
    }

    public function getNameAttribute(): ?string
    {
        return $this->translated('name');
    }

    public function getTitleAttribute(): ?string
    {
        return $this->translated('title');
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->translated('description');
    }
}
