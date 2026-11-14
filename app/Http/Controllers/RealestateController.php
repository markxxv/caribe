<?php

namespace App\Http\Controllers;

use App\Models\Realestate\Country;
use App\Models\Realestate\City;
use App\Models\Realestate\Realestate;
use App\Support\Realestate\CatalogFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RealestateController extends Controller
{
    public function index(Request $request, ?string $filters = null): Response|RedirectResponse
    {
        $state = CatalogFilters::parse($filters);
        $canonicalUrl = CatalogFilters::url($state);

        // All filter dimensions use stable, human-readable path segments.
        if (($filters ?? '') !== CatalogFilters::path($state)) {
            return redirect()->to($canonicalUrl, 301);
        }

        $query = Realestate::query()
            ->where('active', true)
            ->where('sold', false)
            ->with(['country', 'region', 'city', 'district', 'media']);

        $selectedLocation = null;

        if ($state['location_type'] !== null) {
            $slugs = $state['location_slugs'];
            $country = Country::query()->where('slug', $slugs[0])->firstOrFail();
            $place = $country;

            if (count($slugs) >= 2) {
                $place = $country->regions()->where('slug', $slugs[1])->firstOrFail();
            }

            if (count($slugs) >= 3) {
                $place = $place->cities()->where('slug', $slugs[2])->firstOrFail();
            }

            if (count($slugs) >= 4) {
                $place = $place->districts()->where('slug', $slugs[3])->firstOrFail();
            }

            $query->where($state['location_type'] . '_id', $place->id);
            $selectedLocation = $place->name;
        }

        if ($state['deal']) {
            $query->where('operation', $state['deal'] === 'buy' ? 'sale' : 'rent');
        }

        if ($state['type']) {
            $query->where('property_type', $state['type']);
        }

        if ($state['currency']) {
            $query->where('currency', $state['currency']);
        }

        foreach (['price' => 'price', 'area' => 'covered_area'] as $key => $column) {
            if ($state[$key . '_min'] !== null) {
                $query->where($column, '>=', $state[$key . '_min']);
            }

            if ($state[$key . '_max'] !== null) {
                $query->where($column, '<=', $state[$key . '_max']);
            }
        }

        if ($state['beds'] !== null) {
            $query->where('bedrooms', '>=', $state['beds']);
        }

        if ($state['baths'] !== null) {
            $query->where('bathrooms', '>=', $state['baths']);
        }

        if ($state['offer']) {
            $query->where('newbuild', $state['offer'] === 'newbuild');
        }

        if ($state['ref']) {
            $query->whereKey($state['ref']);
        }

        if ($state['sort'] === 'price-low' || $state['sort'] === 'price-high') {
            $query->orderByRaw('CASE WHEN price IS NULL THEN 1 ELSE 0 END')
                ->orderBy('price', $state['sort'] === 'price-low' ? 'asc' : 'desc')
                ->orderByDesc('id');
        } else {
            $query->orderByDesc('published_at')->orderByDesc('id');
        }

        // The first unfiltered result is highlighted separately on page 1.
        // Exclude it from the paginated grid on EVERY page so each grid has
        // 12 distinct entries and pagination never repeats or skips a property.
        $hasFilters = CatalogFilters::path(array_replace($state, ['page' => 1])) !== '';
        $featuredRecord = ! $hasFilters ? (clone $query)->first() : null;

        if ($featuredRecord) {
            $query->whereKeyNot($featuredRecord->getKey());
        }

        $properties = $query->paginate(12, ['*'], 'page', $state['page']);
        abort_if($state['page'] > $properties->lastPage(), 404);

        $featured = $state['page'] === 1 ? $featuredRecord : null;
        $featuredOffset = $featuredRecord ? 1 : 0;
        $catalogTotal = $properties->total() + $featuredOffset;

        // Present only geography that exists in the current database.
        $locations = [];
        foreach (Country::query()->with('regions.cities.districts')->orderBy('name')->get() as $country) {
            $countryPath = $country->slug;
            $locations[] = ['value' => 'country/' . $countryPath, 'label' => $country->name, 'context' => 'Страна'];
            foreach ($country->regions as $region) {
                $regionPath = $countryPath . '/' . $region->slug;
                $locations[] = ['value' => 'region/' . $regionPath, 'label' => $region->name, 'context' => $country->name . ' · Регион'];
                foreach ($region->cities as $city) {
                    $cityPath = $regionPath . '/' . $city->slug;
                    $locations[] = ['value' => 'city/' . $cityPath, 'label' => $city->name, 'context' => $region->name . ' · Город'];
                    foreach ($city->districts as $district) {
                        $locations[] = ['value' => 'district/' . $cityPath . '/' . $district->slug, 'label' => $district->name, 'context' => $city->name . ' · Район'];
                    }
                }
            }
        }

        $types = CatalogFilters::TYPES;
        $currencies = CatalogFilters::CURRENCIES;
        $metaTitle = match ($state['deal']) {
            'buy' => 'Недвижимость на продажу',
            'rent' => 'Недвижимость в аренду',
            default => 'Недвижимость в Латинской Америке',
        };

        if ($state['type']) {
            $metaTitle .= ' · ' . $types[$state['type']];
        }

        if ($selectedLocation) {
            $metaTitle .= ' · ' . $selectedLocation;
        }

        $metaTitle .= ' — Visita Caribe';
        $indexable = $state['price_min'] === null && $state['price_max'] === null
            && $state['area_min'] === null && $state['area_max'] === null
            && $state['beds'] === null && $state['baths'] === null
            && $state['ref'] === null && $state['sort'] === 'recent'
            && $state['page'] === 1
            && $state['location_type'] !== 'city';

        $cityHighlights = collect();
        if (! $hasFilters && $state['page'] === 1) {
            $cityHighlights = City::query()
                ->where(function ($query) {
                    $query->whereNotNull('description')->where('description', '<>', '')
                        ->orWhereHas('realestates', fn ($properties) => $properties
                            ->where('active', true)->where('sold', false));
                })
                ->with(['region.country', 'media'])
                ->orderBy('name')
                ->limit(6)
                ->get();
        }

        return response()->view('realestate.index', compact(
            'properties', 'state', 'locations', 'types', 'currencies',
            'selectedLocation', 'canonicalUrl', 'hasFilters', 'indexable', 'metaTitle',
            'featured', 'featuredOffset', 'catalogTotal', 'cityHighlights'
        ))->header('X-Robots-Tag', $indexable ? 'index, follow' : 'noindex, follow')
            ->header('Cache-Control', 'public, max-age=120');
    }

    public function show(string $url): Response
    {
        $realestate = Realestate::query()
            ->where('slug', $url)
            ->with(['country', 'region', 'city', 'district', 'media'])
            ->firstOrFail();

        // Drafts are available for review in the admin session, but not publicly.
        abort_unless($realestate->active || auth()->check(), 404);

        return response()->view('realestate.show', compact('realestate'))
            ->header('X-Robots-Tag', $realestate->active && ! $realestate->sold ? 'index, follow' : 'noindex, nofollow')
            ->header('Cache-Control', $realestate->active ? 'public, max-age=300' : 'private, no-store');
    }
}
