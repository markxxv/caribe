<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = ['title', 'country_id', 'active', 'items'];

    protected $casts = [
        'active' => 'boolean',
        'items' => 'array',
    ];

    protected $attributes = [
        'active' => true,
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function getPublicSlugAttribute(): string
    {
        $slug = Str::slug($this->title);

        return $this->id . ($slug !== '' ? '-' . $slug : '');
    }

    public function getUrlAttribute(): string
    {
        return route('events.show', ['event' => $this->public_slug]);
    }

    public function chronologicalItems(): Collection
    {
        return collect($this->items ?? [])
            ->filter(function ($item): bool {
                if (! is_array($item) || ! is_string($item['date'] ?? null) || ! filled($item['title'] ?? null)) {
                    return false;
                }

                return preg_match('/^(\d{4})-(\d{2})-(\d{2})$/D', $item['date'], $parts)
                    && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]);
            })
            ->sortBy('date')
            ->values()
            ->map(function (array $item): array {
                $date = CarbonImmutable::parse($item['date'])->locale('ru');
                $url = $item['map_url'] ?? null;

                return [
                    ...$item,
                    'day' => $date->format('j'),
                    'month' => Str::after($date->isoFormat('D MMMM'), ' '),
                    'year' => $date->format('Y'),
                    'map_url' => is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
                        && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) ? $url : null,
                ];
            });
    }
}
