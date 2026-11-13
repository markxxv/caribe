<?php

namespace App\Models\Realestate;

use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

abstract class BaseModel extends Model implements HasMedia
{
    use HasSlug;
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected function slugSource(): string
    {
        return 'name';
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom($this->slugSource())
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Fit::Crop first scales the entire original, then trims to the target
        // aspect ratio. crop() cuts a fixed pixel rectangle from the original.
        // Filament uses this thumbnail in a square image column.
        $this->addMediaConversion('thumb')
            ->performOnCollections('cover')
            ->fit(Fit::Crop, 400, 400)
            ->format('webp')
            ->nonQueued();

        // Property catalog cards are 1.3:1; match that ratio exactly.
        $this->addMediaConversion('cover')
            ->performOnCollections('cover')
            ->fit(Fit::Crop, 780, 600)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('big')
            ->performOnCollections('cover')
            ->width(1400)
            ->format('webp')
            ->nonQueued();
    }
}
