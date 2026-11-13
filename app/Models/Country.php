<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $guarded = ['id'];

    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_country');
    }

    public function tours()
    {
        return $this->belongsToMany(Tour::class, 'country_tour');
    }

    public function news()
    {
        return $this->belongsToMany(News::class, 'country_news');
    }

    public function personalBlogs()
    {
        return $this->belongsToMany(PersonalBlog::class, 'country_personal_blog');
    }

}
