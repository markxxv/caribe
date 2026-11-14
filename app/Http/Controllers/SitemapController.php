<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\News;
use App\Models\PersonalBlog;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\Tour;
use App\Models\Realestate\Realestate;
use App\Models\Realestate\City;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        // Only canonical, public and indexable URLs belong in the sitemap.
        $entries = collect([
            ['url' => route('home')],
            ['url' => route('tours.index')],
            ['url' => route('tours.argentina')],
            ['url' => route('blog.index')],
            ['url' => route('news.index')],
            ['url' => route('article.index')],
            ['url' => route('recipes.index')],
            ['url' => route('realestate.index')],
            ['url' => route('services')],
            ['url' => route('contact')],
        ]);

        Tour::query()
            ->where('active', true)
            ->whereNotNull('slug')
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->chunkById(200, function ($tours) use ($entries) {
                foreach ($tours as $tour) {
                    $entries->push([
                        'url' => route('tours.show', ['slug' => $tour->slug]),
                        'lastmod' => $tour->updated_at?->toAtomString(),
                    ]);
                }
            });

        Blog::query()
            ->where('active', true)
            ->whereNotNull('slug')
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->chunkById(200, function ($blogs) use ($entries) {
                foreach ($blogs as $blog) {
                    $entries->push([
                        'url' => route('blog.show', ['slug' => $blog->slug]),
                        'lastmod' => $blog->updated_at?->toAtomString(),
                    ]);
                }
            });

        BlogCategory::query()
            ->where('active', true)
            ->whereHas('blogs', fn ($q) => $q->where('active', true))
            ->orderBy('sort_order')
            ->pluck('slug')
            ->each(fn (string $slug) => $entries->push([
                'url' => route('blog.category', ['category' => $slug]),
            ]));

        // All published editorial categories, including emigration.
        // A category archive is a valid page only when its name is present
        // and it has at least one published article.
        PersonalBlog::query()
            ->where('active', true)
            ->whereNotNull('category_slug')
            ->where('category_slug', '<>', '')
            ->whereNotNull('category_name')
            ->where('category_name', '<>', '')
            ->distinct()
            ->orderBy('category_slug')
            ->pluck('category_slug')
            ->each(function (string $slug) use ($entries) {
                $entries->push([
                    'url' => route('article.index', ['category' => $slug]),
                ]);
            });

        RecipeCategory::query()
            ->where('active', true)
            ->whereHas('recipes', fn ($q) => $q->where('active', true))
            ->orderBy('sort_order')
            ->pluck('slug')
            ->each(fn (string $slug) => $entries->push([
                'url' => route('recipes.category', ['category' => $slug]),
            ]));

        foreach ([News::class, PersonalBlog::class, Recipe::class] as $model) {
            $model::query()
                ->where('active', true)
                ->whereNotNull('slug')
                ->select(['id', 'slug', 'updated_at'])
                ->orderBy('id')
                ->chunkById(200, function ($records) use ($entries) {
                    foreach ($records as $record) {
                        $entries->push([
                            'url' => $record->url,
                            'lastmod' => $record->updated_at?->toAtomString(),
                        ]);
                    }
                });
        }

        City::query()
            ->where(function ($query) {
                $query->where(function ($descriptions) {
                    $descriptions->whereNotNull('description')->where('description', '<>', '');
                })->orWhereHas('realestates', fn ($properties) => $properties
                    ->where('active', true)->where('sold', false)->where('operation', 'sale'));
            })
            ->with('region.country')
            ->orderBy('id')
            ->chunkById(200, function ($cities) use ($entries) {
                foreach ($cities as $city) {
                    $entries->push([
                        'url' => $city->publicUrl(),
                        'lastmod' => $city->updated_at?->toAtomString(),
                    ]);
                }
            });

        Realestate::query()
            ->where('active', true)
            ->where('sold', false)
            ->whereNotNull('slug')
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->chunkById(200, function ($properties) use ($entries) {
                foreach ($properties as $property) {
                    $entries->push([
                        'url' => route('realestate.show', ['url' => $property->slug]),
                        'lastmod' => $property->updated_at?->toAtomString(),
                    ]);
                }
            });

        return response()
            ->view('seo.sitemap', ['entries' => $entries])
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
