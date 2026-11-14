<?php

namespace App\Http\Controllers;

use App\Models\Realestate\City;
use App\Models\Realestate\Realestate;
use App\Support\Realestate\CatalogFilters;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class RealestateCityController extends Controller
{
    public function show(string $country, string $region, string $city, int $page = 1): Response
    {
        $place = City::query()
            ->with(['region.country', 'media'])
            ->where('slug', $city)
            ->whereHas('region', fn ($query) => $query
                ->where('slug', $region)
                ->whereHas('country', fn ($countries) => $countries->where('slug', $country)))
            ->firstOrFail();

        $properties = Realestate::query()
            ->where('city_id', $place->id)
            ->where('active', true)
            ->where('sold', false)
            ->with(['city', 'media'])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate(9, ['*'], 'page', $page);

        abort_if($page > $properties->lastPage(), 404);

        $baseUrl = $place->publicUrl();
        $canonicalUrl = $page === 1
            ? $baseUrl
            : route('realestate.city.page', [
                'country' => $country, 'region' => $region, 'city' => $city, 'page' => $page,
            ]);

        $descriptionHtml = preg_replace('~</(?:p|div|h[1-6]|li)>~i', ' ', $place->description ?? '');
        $description = Str::squish(html_entity_decode(strip_tags($descriptionHtml), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $metaTitle = $place->meta_title ?: 'Недвижимость в городе ' . $place->name . ' — Visita Caribe';
        $metaDescription = $place->meta_description
            ?: ($description !== ''
                ? Str::limit($description, 160)
                : 'Продажа и аренда недвижимости: ' . $place->name . '. Каталог объектов с фотографиями, ценами и характеристиками.');

        if ($page > 1) {
            $metaTitle = $metaTitle . ' · Страница ' . $page;
        }

        $cover = $place->getFirstMedia('cover');
        $coverUrl = $cover
            ? ($cover->hasGeneratedConversion('big') ? $cover->getUrl('big') : $cover->getUrl())
            : null;

        $baseQuery = $place->realestates()->where('active', true)->where('sold', false);
        $saleCount = (clone $baseQuery)->where('operation', 'sale')->count();
        $rentCount = (clone $baseQuery)->where('operation', 'rent')->count();
        $indexable = $page === 1 && ($description !== '' || $properties->total() > 0);

        $nearbyCities = $place->region->cities()
            ->whereKeyNot($place->id)
            ->where(function ($query) {
                $query->whereNotNull('description')->where('description', '<>', '')
                    ->orWhereHas('realestates', fn ($properties) => $properties
                        ->where('active', true)->where('sold', false));
            })
            ->with(['region.country', 'media'])
            ->orderBy('name')
            ->limit(4)
            ->get();

        $types = CatalogFilters::TYPES;
        $locationPath = implode('/', [$country, $region, $city]);

        return response()->view('realestate.city', compact(
            'place', 'properties', 'canonicalUrl', 'metaTitle', 'metaDescription',
            'coverUrl', 'saleCount', 'rentCount', 'indexable', 'nearbyCities',
            'types', 'locationPath', 'description'
        ))
            ->header('X-Robots-Tag', $indexable ? 'index, follow' : 'noindex, follow')
            ->header('Cache-Control', 'public, max-age=120');
    }
}
