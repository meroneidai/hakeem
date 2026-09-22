<?php

namespace App\Models\Concerns;

use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasRatings
{
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scopeWithRatings(Builder $query): Builder
    {
        return $query
            ->withAvg(['reviews as rating_average' => fn (Builder $reviews) => $reviews->visible()], 'overall')
            ->withCount(['reviews as rating_count' => fn (Builder $reviews) => $reviews->visible()]);
    }

    public function ratingAverage(): ?float
    {
        $value = $this->getAttribute('rating_average');

        if ($value === null) {
            return null;
        }

        return round((float) $value, 1);
    }

    public function ratingCount(): int
    {
        return (int) ($this->getAttribute('rating_count') ?? 0);
    }

    /**
     * @return array{average: ?float, count: int}
     */
    public function ratingSummary(): array
    {
        return [
            'average' => $this->ratingAverage(),
            'count' => $this->ratingCount(),
        ];
    }
}
