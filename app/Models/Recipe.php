<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Recipe extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasSlug;
    use \App\Models\Concerns\HasPublicPage;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
        'content' => 'array',
        'published_at' => 'datetime',
        'tags' => 'array',
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
            ->addMediaCollection('recipe_cover')
            ->singleFile(); // одна обложка

        $this->addMediaCollection('recipe_builder_image');
        $this->addMediaCollection('recipe_builder_gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Важно: чтобы конверсии не цеплялись на другие коллекции
        $this->addMediaConversion('thumb')
            ->performOnCollections('recipe_cover')
            ->width(400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('card')
            ->performOnCollections('recipe_cover')
            ->fit(Fit::Crop, 600, 400)
            ->format('webp')
            ->nonQueued();

        // Square editorial thumbnail: crop the largest possible area from the
        // original, then resize. Never use focalCrop() on raw source pixels.
        // A media item's optional focus properties override the centre.
        $focalX = max(0, min(100, (int) ($media?->getCustomProperty('square_focal_x', 50) ?? 50)));
        $focalY = max(0, min(100, (int) ($media?->getCustomProperty('square_focal_y', 50) ?? 50)));

        $this->addMediaConversion('square_card')
            ->performOnCollections('recipe_cover')
            ->focalCropAndResize(720, 720, $focalX, $focalY)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('cover')
            ->performOnCollections('recipe_cover')
            ->width(1200)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('big')
            ->performOnCollections('recipe_cover')
            ->width(1400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('builder')
            ->performOnCollections('recipe_builder_image', 'recipe_builder_gallery')
            ->width(1200)
            ->format('webp')
            ->nonQueued();
    }

    public function category()
    {
        return $this->belongsTo(RecipeCategory::class, 'recipe_category_id');
    }

    public static function pageRouteName(): string
    {
        return 'recipes.show';
    }

    public static function mediaPrefix(): string
    {
        return 'recipe';
    }
}
