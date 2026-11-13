<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class PersonalBlog extends Model implements HasMedia
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
            ->addMediaCollection('personal_blog_cover')
            ->singleFile(); // одна обложка

        $this->addMediaCollection('personal_blog_builder_image');
        $this->addMediaCollection('personal_blog_builder_gallery');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Важно: чтобы конверсии не цеплялись на другие коллекции
        $this->addMediaConversion('thumb')
            ->performOnCollections('personal_blog_cover')
            ->width(400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('cover')
            ->performOnCollections('personal_blog_cover')
            ->width(1200)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('big')
            ->performOnCollections('personal_blog_cover')
            ->width(1400)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('builder')
            ->performOnCollections('personal_blog_builder_image', 'personal_blog_builder_gallery')
            ->width(1200)
            ->format('webp')
            ->nonQueued();
    }

    public function countries()
    {
        return $this->belongsToMany(Country::class, 'country_personal_blog');
    }

    public static function pageRouteName(): string
    {
        return 'article.show';
    }

    public static function mediaPrefix(): string
    {
        return 'personal_blog';
    }
}
