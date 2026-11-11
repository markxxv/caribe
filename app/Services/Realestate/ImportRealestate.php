<?php

namespace App\Services\Realestate;

use App\Jobs\ImportRealestatePhoto;
use App\Support\Realestate\PhotoQuality;
use App\Models\Realestate\City;
use App\Models\Realestate\Country;
use App\Models\Realestate\District;
use App\Models\Realestate\Realestate;
use App\Models\Realestate\Region;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ImportRealestate
{
    private const FIELDS = [
        'title', 'property_type', 'operation', 'price', 'currency', 'expenses',
        'expenses_currency', 'land_area', 'covered_area', 'semi_covered_area',
        'rooms', 'bedrooms', 'bathrooms', 'toilets', 'parking_spaces',
        'parking_type', 'floors', 'floor_number', 'construction_year', 'condition',
        'orientation', 'land_front', 'land_depth', 'address', 'latitude', 'longitude',
        'features', 'amenities', 'source_platform',
    ];

    public function handle(array $item): array
    {
        $sourceUrl = $this->canonicalUrl($item['source_url']);
        $created = false;

        $property = DB::transaction(function () use ($item, $sourceUrl, &$created): Realestate {
            // Serializes concurrent writes to the same source URL via the DB unique index.
            $property = Realestate::where('source_url', $sourceUrl)->lockForUpdate()->first();

            if (! $property) {
                $property = new Realestate();
                $property->source_url = $sourceUrl;
                $property->active = false;
                $property->newbuild = false;
                $property->sold = false;
                $created = true;
            }

            // Published properties may have been reviewed/edited manually.
            if ($created || ! $property->active) {
                $property->fill(Arr::only($item, self::FIELDS));

                if (array_key_exists('description', $item)) {
                    // Scraped content is treated as plain text, never untrusted HTML.
                    $property->description = nl2br(e($item['description'] ?? ''));
                }

                $geography = $this->geography($item);
                foreach ($geography as $key => $value) {
                    $property->{$key} = $value;
                }
            }

            $property->save();

            return $property;
        });

        // Store the property immediately; CDN downloads run in the database queue.
        $queued = 0;
        $urls = array_values(array_unique($item['photos'] ?? []));
        $existingPhotos = $property->getMedia('photos');

        // The new parser explicitly identifies the complete official photo
        // list. Remove previously scraped recommendation-card thumbnails
        // only for drafts, leaving published or manually uploaded media intact.
        if (! $property->active && ! empty($urls) && ($item['photos_complete'] ?? false)) {
            $sourceKeys = array_fill_keys(array_map(
                fn (string $url): string => basename((string) parse_url($url, PHP_URL_PATH)),
                $urls
            ), true);

            foreach ($existingPhotos as $media) {
                $sourceUrl = $media->getCustomProperty('source_url');
                $key = $media->getCustomProperty('source_key')
                    ?: basename((string) parse_url($sourceUrl ?? '', PHP_URL_PATH));

                if ($sourceUrl && ! isset($sourceKeys[$key])) {
                    $media->delete();
                }
            }

            $property->unsetRelation('media');
            $existingPhotos = $property->getMedia('photos');
        }

        foreach ($urls as $url) {
            $sourceKey = basename((string) parse_url($url, PHP_URL_PATH));

            $existing = $existingPhotos->first(
                fn ($media): bool => $media->getCustomProperty('source_url') === $url
                    || ($sourceKey !== '' && $media->getCustomProperty('source_key') === $sourceKey)
            );

            if ($existing && ! PhotoQuality::isUpgrade(
                $url, $existing->getCustomProperty('source_url')
            )) {
                continue;
            }

            ImportRealestatePhoto::dispatch($property->id, $url)
                ->onConnection('database')
                ->onQueue('realestate-photos');

            ++$queued;
        }

        return [
            'id' => $property->id,
            'source_url' => $sourceUrl,
            'created' => $created,
            'active' => $property->active,
            'photos_added' => 0,
            'photos_queued' => $queued,
            'photo_count' => $existingPhotos->count(),
            'warnings' => [],
        ];
    }

    private function canonicalUrl(string $value): string
    {
        $parts = parse_url(trim($value));

        if (! $parts || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            throw new RuntimeException('Invalid source URL');
        }

        $host = strtolower($parts['host'] ?? '');
        if ($host === '' || isset($parts['user']) || isset($parts['pass'])) {
            throw new RuntimeException('Invalid source URL host');
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = rtrim($parts['path'] ?? '/', '/') ?: '/';

        // Tracking query parameters, fragments and trailing slashes never affect identity.
        return strtolower($parts['scheme']) . '://' . $host . $port . $path;
    }

    private function geography(array $item): array
    {
        $ids = [];
        $country = filled($item['country'] ?? null)
            ? Country::firstOrCreate(['slug' => Str::slug(trim($item['country']))], ['name' => trim($item['country'])])
            : null;

        if ($country) {
            $ids['country_id'] = $country->id;
        }

        $region = $country && filled($item['region'] ?? null)
            ? Region::firstOrCreate(
                ['country_id' => $country->id, 'slug' => Str::slug(trim($item['region']))],
                ['name' => trim($item['region'])]
            )
            : null;
        if ($region) {
            $ids['region_id'] = $region->id;
        }

        $city = $region && filled($item['city'] ?? null)
            ? City::firstOrCreate(
                ['region_id' => $region->id, 'slug' => Str::slug(trim($item['city']))],
                ['name' => trim($item['city'])]
            )
            : null;
        if ($city) {
            $ids['city_id'] = $city->id;
        }

        $district = $city && filled($item['district'] ?? null)
            ? District::firstOrCreate(
                ['city_id' => $city->id, 'slug' => Str::slug(trim($item['district']))],
                ['name' => trim($item['district'])]
            )
            : null;
        if ($district) {
            $ids['district_id'] = $district->id;
        }

        return $ids;
    }

}
