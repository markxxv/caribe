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

    protected static function booted(): void
    {
        static::saving(function (Event $event): void {
            if ($event->isDirty('items') && is_array($event->items)) {
                $event->items = self::withItemSlugs($event->items);
            }
        });
    }

    // Keep detail links stable when a collection is reordered or edited.
    // Legacy SQL inserts without item slugs use the same deterministic fallback.
    private static function withItemSlugs(array $items): array
    {
        $used = [];
        $reserved = [];
        foreach ($items as $item) {
            if (is_array($item) && is_string($item['slug'] ?? null) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $item['slug'])) {
                $reserved[rtrim(Str::substr($item['slug'], 0, 180), '-')] = true;
            }
        }
        foreach ($items as &$item) {
            if (! is_array($item) || ! is_string($item['title'] ?? null) || ! is_string($item['date'] ?? null)) {
                continue;
            }

            $stored = $item['slug'] ?? null;
            $hasStored = is_string($stored) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $stored);
            $base = $hasStored
                ? rtrim(Str::substr($stored, 0, 180), '-')
                : (rtrim(Str::substr(Str::slug($item['date'] . ' ' . $item['title']), 0, 160), '-') ?: 'event');
            $slug = $base;
            $number = 2;
            if (isset($used[$slug]) || (! $hasStored && isset($reserved[$slug]))) {
                $base = rtrim(Str::substr($base, 0, 160), '-');
                do {
                    $slug = $base . '-' . $number++;
                } while (isset($used[$slug]) || isset($reserved[$slug]));
            }
            $used[$slug] = true;
            $item['slug'] = $slug;
        }
        unset($item);

        return $items;
    }

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
        return collect(self::withItemSlugs($this->items ?? []))
            ->filter(function ($item): bool {
                if (! is_array($item) || ! is_string($item['date'] ?? null) || ! is_string($item['title'] ?? null) || ! filled($item['title'])) {
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
                    'location' => is_string($item['location'] ?? null) ? $item['location'] : null,
                    'announcement' => is_string($item['announcement'] ?? null) ? $item['announcement'] : null,
                    'day' => $date->format('j'),
                    'month' => Str::after($date->isoFormat('D MMMM'), ' '),
                    'year' => $date->format('Y'),
                    'detail_url' => route('events.detail', ['slug' => $this->slug, 'itemSlug' => $item['slug']]),
                    'map_url' => is_string($url) && filter_var($url, FILTER_VALIDATE_URL)
                        && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) ? $url : null,
                ];
            });
    }
}
