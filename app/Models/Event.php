<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Event extends Model
{
    use HasSlug;

    protected $fillable = ['title', 'slug', 'country_id', 'active', 'items', 'created_at'];

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

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(fn (Event $event) => rtrim(Str::substr(Str::slug($event->title), 0, 240), '-') ?: 'event')
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(240)
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getUrlAttribute(): string
    {
        return route('events.show', ['slug' => $this->slug]);
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
