<?php

namespace App\Http\Controllers;

use App\Models\Realestate\City;
use App\Models\Realestate\Realestate;
use App\Support\Realestate\CatalogFilters;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class RealestateCityController extends Controller
{
    public function show(string $country, string $region, string $city, int $page = 1): Response
    {
        $place = $this->findCity($country, $region, $city);

        $properties = Realestate::query()
            ->where('city_id', $place->id)
            ->where('active', true)
            ->where('sold', false)
            ->where('operation', 'sale')
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
                : 'Недвижимость в городе ' . $place->name . '. Дома и квартиры на продажу с фотографиями, ценами и характеристиками.');

        if ($page > 1) {
            $metaTitle = $metaTitle . ' · Страница ' . $page;
        }

        $coverUrl = $place->publicCoverUrl();

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
        $locationSlugs = [$country, $region, $city];
        $locationPath = implode('/', $locationSlugs);
        $cityFilters = array_replace(CatalogFilters::defaults(), [
            'deal' => 'buy',
            'location_type' => 'city',
            'location_slugs' => $locationSlugs,
        ]);
        $housesUrl = CatalogFilters::url(array_replace($cityFilters, ['type' => 'house']));
        $apartmentsUrl = CatalogFilters::url(array_replace($cityFilters, ['type' => 'apartment']));

        return response()->view('realestate.city', compact(
            'place', 'properties', 'canonicalUrl', 'metaTitle', 'metaDescription',
            'coverUrl', 'indexable', 'nearbyCities',
            'types', 'locationPath', 'housesUrl', 'apartmentsUrl', 'description'
        ))
            ->header('X-Robots-Tag', $indexable ? 'index, follow' : 'noindex, follow')
            ->header('Cache-Control', 'public, max-age=120');
    }

    /**
     * Images already uploaded to Filament's private local disk must still
     * be readable on public city pages. Serve only the city's cover image,
     * never arbitrary media paths or private uploads.
     */
    public function cover(Request $request, string $country, string $region, string $city): StreamedResponse
    {
        $place = $this->findCity($country, $region, $city);
        $media = $place->getFirstMedia('cover');

        abort_unless($media && in_array($media->mime_type, [
            'image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif',
        ], true), 404);

        $conversion = $media->hasGeneratedConversion('big') ? 'big' : '';
        $disk = $conversion !== '' ? ($media->conversions_disk ?: $media->disk) : $media->disk;

        // A stale generated-conversion flag must not make the cover disappear.
        if ($conversion !== '' && ! Storage::disk($disk)->exists($media->getPathRelativeToRoot($conversion))) {
            $conversion = '';
            $disk = $media->disk;
        }

        abort_unless(Storage::disk($disk)->exists($media->getPathRelativeToRoot($conversion)), 404);

        $response = $media->toInlineResponse($request, $conversion);
        $response->headers->set('Cache-Control', 'public, max-age=86400');

        return $response;
    }

    private function findCity(string $country, string $region, string $city): City
    {
        return City::query()
            ->with(['region.country', 'media'])
            ->where('slug', $city)
            ->whereHas('region', fn ($query) => $query
                ->where('slug', $region)
                ->whereHas('country', fn ($countries) => $countries->where('slug', $country)))
            ->firstOrFail();
    }
}
