<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class News extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasSlug;
    use \App\Models\Concerns\HasPublicPage;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
        'content' => 'array',
        'published_at' => 'datetime',
    ];

    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('title')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }


    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('news_cover')
            ->singleFile(); // одна обложка

        $this->addMediaCollection('news_builder_image');
        $this->addMediaCollection('news_builder_gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Важно: чтобы конверсии не цеплялись на другие коллекции
        $this->addMediaConversion('thumb')
            ->performOnCollections('news_cover')
            ->width(400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('card')
            ->performOnCollections('news_cover')
            ->fit(Fit::Crop, 600, 400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('cover')
            ->performOnCollections('news_cover')
            ->width(1200)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('big')
            ->performOnCollections('news_cover')
            ->width(1400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('builder')
            ->performOnCollections('news_builder_image', 'news_builder_gallery')
            ->width(1200)
            ->format('webp')
            ->nonQueued();
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'country_news');
    }

    public static function pageRouteName(): string
    {
        return 'news.show';
    }

    public static function mediaPrefix(): string
    {
        return 'news';
    }
}
