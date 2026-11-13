<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Общие свойства публичной страницы контента (блог, туры, новости, статьи, рецепты).
 */
trait HasPublicPage
{
    /** Имя маршрута страницы записи (принимает параметр {slug}). */
    abstract public static function pageRouteName(): string;

    /** Префикс медиаколлекций: {prefix}_cover, {prefix}_builder_image, {prefix}_builder_gallery. */
    abstract public static function mediaPrefix(): string;

    public function getUrlAttribute(): string
    {
        return route(static::pageRouteName(), ['slug' => $this->slug]);
    }

    public function getCoverUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia(static::mediaPrefix() . '_cover');

        return $media ? ($media->getAvailableUrl(['cover']) ?: $media->getUrl()) : null;
    }

    /** Дата публикации из Craft, а если её нет — дата создания записи. */
    public function getDisplayDateAttribute()
    {
        return $this->published_at ?? $this->created_at;
    }

    /** meta_description или начало первого текстового блока. */
    public function getExcerptAttribute(): string
    {
        if (filled($this->meta_description)) {
            return (string) $this->meta_description;
        }

        foreach ((array) $this->content as $block) {
            if (($block['type'] ?? '') !== 'text') {
                continue;
            }
            $text = html_entity_decode(strip_tags((string) ($block['data']['text'] ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $text = trim(preg_replace('/\s+/u', ' ', $text));
            if ($text !== '') {
                return Str::limit($text, 180);
            }
        }

        return '';
    }
}
