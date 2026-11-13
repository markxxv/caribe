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

        // Keep the largest possible square of the ORIGINAL, then resize to 720px.
        // focalCrop() alone cuts only 720 source pixels and can lose the subject.
        // These four editorial images have manually chosen composition anchors.
        $squareFocalPoints = [
            'istoricheskiy-siti-tur-po-buenos-ayresu' => [50, 57],
            'tur-v-bariloche-s-reyndzherom-4kh4' => [40, 52],
            'tur-po-severu-argentiny-khukhuy-tukuman-salta-katamarka' => [50, 50],
            'tur-po-argentine-tropicheskaya-selva-vodopady-iguasu-i-iezuitskie-missii' => [76, 50],
        ];

        $slug = $media?->collection_name === 'tour_cover' ? $media->model?->slug : null;
        [$defaultX, $defaultY] = $squareFocalPoints[$slug] ?? [50, 50];

        // Optional per-photo adjustments take priority over the editorial defaults.
        $focalX = max(0, min(100, (int) ($media?->getCustomProperty('square_focal_x', $defaultX) ?? $defaultX)));
        $focalY = max(0, min(100, (int) ($media?->getCustomProperty('square_focal_y', $defaultY) ?? $defaultY)));

        // New conversion name deliberately bypasses stale cached /square.webp URLs.
        $this->addMediaConversion('square_card')
            ->performOnCollections('tour_cover')
            ->focalCropAndResize(720, 720, $focalX, $focalY)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('big')
            ->performOnCollections('tour_cover')
            ->width(1400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('builder')
            ->performOnCollections('tour_builder', 'tour_builder_image', 'tour_builder_gallery')
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
