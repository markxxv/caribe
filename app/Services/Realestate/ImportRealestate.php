<?php

namespace App\Services\Realestate;

use App\Models\Realestate\City;
use App\Models\Realestate\Country;
use App\Models\Realestate\District;
use App\Models\Realestate\Realestate;
use App\Models\Realestate\Region;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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

        $warnings = [];
        $photosAdded = 0;

        foreach (array_unique($item['photos'] ?? []) as $url) {
            if ($property->getMedia('photos')->contains(
                fn ($media): bool => $media->getCustomProperty('source_url') === $url
            )) {
                continue;
            }

            try {
                $path = $this->downloadImage($url);
                try {
                    $ext = match (mime_content_type($path)) {
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        default => 'jpg',
                    };

                    $property->addMedia($path)
                        ->usingFileName(Str::random(24) . '.' . $ext)
                        ->withCustomProperties(['source_url' => $url])
                        ->toMediaCollection('photos', 'public');

                    ++$photosAdded;
                } finally {
                    if (is_file($path)) {
                        @unlink($path);
                    }
                }
            } catch (\Throwable $e) {
                report($e);
                $warnings[] = 'Failed to download image: ' . $url;
            }
        }

        if (! $property->getFirstMedia('cover') && ($first = $property->getFirstMedia('photos'))) {
            try {
                $first->copy($property, 'cover', 'public');
            } catch (\Throwable $e) {
                report($e);
                $warnings[] = 'Could not create cover from first photo';
            }
        }

        return [
            'id' => $property->id,
            'source_url' => $sourceUrl,
            'created' => $created,
            'active' => $property->active,
            'photos_added' => $photosAdded,
            'photo_count' => $property->getMedia('photos')->count(),
            'warnings' => $warnings,
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

    private function downloadImage(string $url): string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $allowed = config('property_import.image_hosts', []);

        if (($parts['scheme'] ?? null) !== 'https' || isset($parts['user']) || isset($parts['pass'])
            || ! in_array($host, $allowed, true)) {
            throw new RuntimeException('Image host not allowed');
        }

        $response = Http::withOptions(['stream' => true, 'allow_redirects' => false])
            ->connectTimeout(8)
            ->timeout(25)
            ->get($url);

        if (! $response->successful() || ! str_starts_with(strtolower($response->header('Content-Type', '')), 'image/')) {
            throw new RuntimeException('Invalid image response');
        }

        $path = tempnam(sys_get_temp_dir(), 'property-photo-');
        if (! $path) {
            throw new RuntimeException('Cannot create temp file');
        }

        try {
            $stream = $response->toPsrResponse()->getBody();
            $maxBytes = config('property_import.max_image_bytes', 15728640);
            $count = 0;
            $file = fopen($path, 'wb');

            if (! $file) {
                throw new RuntimeException('Cannot open temp file');
            }

            try {
                while (! $stream->eof()) {
                    $chunk = $stream->read(8192);
                    if ($chunk === '') {
                        break;
                    }

                    $count += strlen($chunk);
                    if ($count > $maxBytes) {
                        throw new RuntimeException('Image is too large');
                    }
                    if (fwrite($file, $chunk) !== strlen($chunk)) {
                        throw new RuntimeException('Failed writing image');
                    }
                }
            } finally {
                fclose($file);
            }

            $mime = mime_content_type($path);
            if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) || ! @getimagesize($path)) {
                throw new RuntimeException('Unsupported image type');
            }

            return $path;
        } catch (\Throwable $e) {
            @unlink($path);
            throw $e;
        }
    }
}
