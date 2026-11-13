<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog;
use App\Models\Country;
use App\Models\News;
use App\Models\Recipe;
use App\Models\Tour;
use App\Support\Content\ListingFilters;
class Front extends Controller
{
    public function index()
    {
        $blogs = Blog::query()
            ->where('active', true)
            ->with(['countries', 'media'])
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->take(6)
            ->get();

        $tours = Tour::query()
            ->where('active', true)
            ->with(['countries', 'media'])
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->take(6)
            ->get();

        $recipes = Recipe::query()
            ->where('active', true)
            ->with('media')
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->limit(5)
            ->get();

        $news = News::query()
            ->where('active', true)
            ->with(['countries', 'media'])
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->limit(4)
            ->get();

        return view('home', compact('blogs', 'tours', 'recipes', 'news'));
    }

    public function blogIndex(Request $request)
    {
        $listingFilters = ListingFilters::parse($request);
        $baseUrl = route('blog.index');
        if ($redirect = ListingFilters::canonicalRedirect($request, ListingFilters::url($baseUrl, $listingFilters))) {
            return $redirect;
        }

        $query = Blog::query()->where('active', true)->with(['countries', 'media']);

        if ($listingFilters['country']) {
            $query->whereHas('countries', fn ($q) => $q->where('countries.slug', $listingFilters['country']));
        }
        if ($listingFilters['search']) {
            $term = $listingFilters['search'];
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$term}%")
                ->orWhere('meta_description', 'like', "%{$term}%"));
        }

        $blogs = $query->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->paginate(12, ['*'], 'page', $listingFilters['page']);

        $countries = Country::query()
            ->whereHas('blogs', fn ($q) => $q->where('active', true))
            ->orderBy('title')->get();

        return view('blog.index', compact('blogs', 'countries', 'listingFilters', 'baseUrl'));
    }

    public function blogShow(string $slug)
    {
        $blog = Blog::query()
            ->where('slug', $slug)
            ->where('active', true)
            ->with(['countries', 'media'])
            ->firstOrFail();

        return view('blog.show', compact('blog'));
    }


    public function toursArgentina(Request $request)
    {
        return $this->toursIndex($request, 'argentina');
    }

    public function toursIndex(Request $request, ?string $forcedCountrySlug = null)
    {
        $listingFilters = ListingFilters::parse($request, true, $forcedCountrySlug);
        $baseUrl = route('tours.index');
        if ($redirect = ListingFilters::canonicalRedirect(
            $request,
            ListingFilters::url($baseUrl, $listingFilters, null, true)
        )) {
            return $redirect;
        }

        $isArgentina = $listingFilters['country'] === 'argentina';
        $selectedCountry = $listingFilters['country']
            ? Country::query()->where('slug', $listingFilters['country'])->first()
            : null;
        $selectedCountryId = $selectedCountry?->id ? (string) $selectedCountry->id : 'all';

        $query = Tour::query()->where('active', true)->with(['countries', 'media']);
        if ($listingFilters['country']) {
            $query->whereHas('countries', fn ($q) => $q->where('countries.slug', $listingFilters['country']));
        }
        if ($listingFilters['search']) {
            $term = $listingFilters['search'];
            $query->where(fn ($q) => $q
                ->where('title', 'like', "%{$term}%")
                ->orWhere('meta_description', 'like', "%{$term}%"));
        }

        $tours = $query->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->paginate(12, ['*'], 'page', $listingFilters['page']);

        $countries = Country::query()
            ->whereHas('tours', fn ($q) => $q->where('active', true))
            ->orderBy('title')->get();

        return view('tours.index', compact(
            'tours', 'countries', 'selectedCountryId', 'selectedCountry', 'isArgentina', 'listingFilters', 'baseUrl'
        ));
    }

    public function toursShow(string $slug)
    {
        $tour = Tour::query()
            ->where('slug', $slug)
            ->where('active', true)
            ->with(['countries', 'media'])
            ->firstOrFail();

        return view('tours.show', compact('tour'));
    }



}
