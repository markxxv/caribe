<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Tour;
use App\Models\Realestate\Realestate;
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
            ['url' => route('realestate.index')],
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
