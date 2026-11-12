<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Blog extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasSlug;

    protected $guarded = ['id'];

    protected $casts = [
        'active' => 'boolean',
        'content' => 'array',
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
            ->addMediaCollection('blog_cover')
            ->singleFile(); // одна обложка

        $this->addMediaCollection('blog_builder');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Важно: чтобы конверсии не цеплялись на другие коллекции
        $this->addMediaConversion('thumb')
            ->performOnCollections('blog_cover')
            ->width(400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('cover')
            ->performOnCollections('blog_cover')
            ->width(1200)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('big')
            ->performOnCollections('blog_cover')
            ->width(1400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('builder')
            ->performOnCollections('blog_builder')
            ->width(1200)
            ->format('webp')
            ->nonQueued();
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'blog_country');
    }

}
