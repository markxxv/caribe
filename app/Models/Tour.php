<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Tour extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasSlug;

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
            ->addMediaCollection('tour_cover')
            ->singleFile(); // одна обложка

        $this->addMediaCollection('tour_builder');
        $this->addMediaCollection('tour_builder_image');
        $this->addMediaCollection('tour_builder_gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('tour_cover')
            ->width(400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('cover')
            ->performOnCollections('tour_cover')
            ->width(1200)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('big')
            ->performOnCollections('tour_cover')
            ->width(1400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('builder')
            ->performOnCollections('tour_builder')
            ->width(1200)
            ->format('webp')
            ->nonQueued();
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'country_tour');
    }
}
