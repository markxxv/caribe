<?php

namespace App\Models\Realestate;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Realestate extends BaseModel
{
    protected $table = 'property_realestates';

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'newbuild' => 'boolean',
            'sold' => 'boolean',
            'features' => 'array',
            'amenities' => 'array',
            'price' => 'decimal:2',
            'expenses' => 'decimal:2',
            'land_area' => 'decimal:2',
            'covered_area' => 'decimal:2',
            'semi_covered_area' => 'decimal:2',
            'land_front' => 'decimal:2',
            'land_depth' => 'decimal:2',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'published_at' => 'datetime',
            'sold_at' => 'datetime',
        ];
    }

    protected function slugSource(): string
    {
        return 'title';
    }

    public function registerMediaCollections(): void
    {
        parent::registerMediaCollections();

        $this->addMediaCollection('photos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        parent::registerMediaConversions($media);

        // Used only by property gallery navigation (116x82), not the square Filament thumbnail.
        $this->addMediaConversion('gallery_thumb')
            ->performOnCollections('cover')
            ->fit(Fit::Crop, 580, 410)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('photo_thumb')
            ->performOnCollections('photos')
            ->width(600)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('photo_large')
            ->performOnCollections('photos')
            ->width(1600)
            ->format('webp')
            ->nonQueued();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }
}
