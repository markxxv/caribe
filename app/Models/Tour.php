<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Tour extends Model implements HasMedia
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

        $this->addMediaConversion('card')
            ->performOnCollections('tour_cover')
            ->fit(Fit::Crop, 600, 400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('cover')
            ->performOnCollections('tour_cover')
            ->width(1200)
            ->format('webp')
            ->nonQueued();

        // Dedicated 1:1 card: generate from the ORIGINAL, never from 'cover'.
        // Defaults are slightly above/left of centre; adjust individual pictures
        // using media custom_properties.square_focal_x / square_focal_y (0-100).
        $focalX = max(0, min(100, (int) ($media?->getCustomProperty('square_focal_x', 45) ?? 45)));
        $focalY = max(0, min(100, (int) ($media?->getCustomProperty('square_focal_y', 40) ?? 40)));

        $this->addMediaConversion('square')
            ->performOnCollections('tour_cover')
            ->focalCrop(720, 720, $focalX, $focalY)
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

    public static function pageRouteName(): string
    {
        return 'tours.show';
    }

    public static function mediaPrefix(): string
    {
        return 'tour';
    }
}
