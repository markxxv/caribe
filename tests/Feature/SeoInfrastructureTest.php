<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Tour;
use App\Models\Realestate\Realestate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_contains_public_canonical_pages_and_published_records_only(): void
    {
        $tour = Tour::create(['title' => 'Published tour for sitemap', 'active' => true]);
        $blog = Blog::create(['title' => 'Published article for sitemap', 'active' => true]);
        $draftTour = Tour::create(['title' => 'Private unpublished tour', 'active' => false]);
        $draftBlog = Blog::create(['title' => 'Private unpublished article', 'active' => false]);

        $property = Realestate::create([
            'title' => 'Published property for sitemap',
            'active' => true,
            'sold' => false,
            'operation' => 'sale',
            'property_type' => 'house',
            'currency' => 'USD',
            'price' => 250000,
        ]);
        $sold = Realestate::create([
            'title' => 'Sold property for sitemap',
            'active' => true,
            'sold' => true,
            'operation' => 'sale',
            'property_type' => 'house',
            'currency' => 'USD',
            'price' => 300000,
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false)
            ->assertSee('<loc>' . route('home') . '</loc>', false)
            ->assertSee('<loc>' . route('tours.index') . '</loc>', false)
            ->assertSee('<loc>' . route('blog.index') . '</loc>', false)
            ->assertSee(route('tours.show', ['slug' => $tour->slug]), false)
            ->assertSee(route('blog.show', ['slug' => $blog->slug]), false)
            ->assertSee(route('realestate.show', ['url' => $property->slug]), false)
            ->assertDontSee(route('tours.show', ['slug' => $draftTour->slug]), false)
            ->assertDontSee(route('blog.show', ['slug' => $draftBlog->slug]), false)
            ->assertDontSee(route('realestate.show', ['url' => $sold->slug]), false);
    }

    public function test_home_and_blog_have_distinct_canonical_and_relevant_meta_descriptions(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="' . route('home') . '"', false)
            ->assertSee('Туры и экскурсии по Аргентине с Еленой Вивас')
            ->assertSee('"@type":"WebSite"', false)
            ->assertSee('"@type":"Organization"', false);

        $this->get('/blog')
            ->assertOk()
            ->assertSee('href="' . route('blog.index') . '"', false)
            ->assertSee('Блог об Аргентине и Латинской Америке')
            ->assertDontSee('северной Испании');
    }

    public function test_filtered_blog_and_real_estate_are_not_indexable(): void
    {
        $this->get('/blog?search=sample')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, follow"', false);

        $this->get('/realestate/price/100000-300000')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, follow')
            ->assertSee('name="robots" content="noindex, follow"', false);
    }

    public function test_sold_properties_are_excluded_from_search_even_when_their_pages_remain_accessible(): void
    {
        $sold = Realestate::create([
            'title' => 'Previously sold house',
            'active' => true,
            'sold' => true,
            'operation' => 'sale',
            'property_type' => 'house',
            'currency' => 'USD',
            'price' => 120000,
        ]);

        $this->get(route('realestate.show', ['url' => $sold->slug]))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_published_blog_and_tour_pages_have_canonicals_and_entity_schema(): void
    {
        $tour = Tour::create(['title' => 'A journey across Argentina', 'active' => true]);
        $blog = Blog::create(['title' => 'A guide to Buenos Aires', 'active' => true]);

        $this->get(route('tours.show', ['slug' => $tour->slug]))
            ->assertOk()
            ->assertSee('rel="canonical" href="' . route('tours.show', ['slug' => $tour->slug]) . '"', false)
            ->assertSee('"@type":"TouristTrip"', false);

        $this->get(route('blog.show', ['slug' => $blog->slug]))
            ->assertOk()
            ->assertSee('rel="canonical" href="' . route('blog.show', ['slug' => $blog->slug]) . '"', false)
            ->assertSee('"@type":"BlogPosting"', false);
    }
}
