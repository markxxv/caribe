<?php

namespace App\Support\Realestate;

final class CatalogFilters
{
    public const TYPES = [
        'house' => 'Дом / вилла',
        'apartment' => 'Квартира',
        'townhouse' => 'Таунхаус',
        'penthouse' => 'Пентхаус',
        'duplex' => 'Дуплекс',
        'land' => 'Участок',
        'commercial' => 'Коммерческая недвижимость',
        'building' => 'Здание',
        'other' => 'Другое',
    ];

    public const CURRENCIES = ['USD', 'ARS', 'EUR', 'BRL', 'UYU', 'CLP'];

    public static function defaults(): array
    {
        return [
            'deal' => null,
            'location_type' => null,
            'location_slugs' => [],
            'type' => null,
            'currency' => null,
            'price_min' => null,
            'price_max' => null,
            'beds' => null,
            'baths' => null,
            'area_min' => null,
            'area_max' => null,
            'offer' => null,
            'ref' => null,
            'sort' => 'recent',
            'page' => 1,
        ];
    }

    public static function parse(?string $path): array
    {
        $filters = self::defaults();
        $segments = $path === null || $path === '' ? [] : explode('/', trim($path, '/'));
        $seen = [];

        for ($i = 0; $i < count($segments);) {
            $token = $segments[$i++];
            $key = in_array($token, ['buy', 'rent'], true) ? 'deal' : $token;
            abort_if(isset($seen[$key]), 404);
            $seen[$key] = true;

            if ($key === 'deal') {
                $filters['deal'] = $token;
                continue;
            }

            if ($key === 'in') {
                $kind = $segments[$i++] ?? null;
                $depth = ['country' => 1, 'region' => 2, 'city' => 3, 'district' => 4][$kind ?? ''] ?? null;
                abort_if($depth === null || count($segments) - $i < $depth, 404);

                $slugs = array_slice($segments, $i, $depth);
                foreach ($slugs as $slug) {
                    abort_unless(preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug), 404);
                }

                $filters['location_type'] = $kind;
                $filters['location_slugs'] = $slugs;
                $i += $depth;
                continue;
            }

            if ($key === 'newbuild' || $key === 'resale') {
                abort_if($filters['offer'] !== null, 404);
                $filters['offer'] = $key;
                continue;
            }

            $value = $segments[$i++] ?? null;
            abort_if($value === null || $value === '', 404);

            switch ($key) {
                case 'type':
                    abort_unless(array_key_exists($value, self::TYPES), 404);
                    $filters['type'] = $value;
                    break;
                case 'currency':
                    abort_unless(in_array($value, self::CURRENCIES, true), 404);
                    $filters['currency'] = $value;
                    break;
                case 'price':
                case 'area':
                    abort_unless(preg_match('/^([0-9]{0,12})-([0-9]{0,12})$/D', $value, $matches), 404);
                    abort_if($matches[1] === '' && $matches[2] === '', 404);
                    $min = $matches[1] !== '' ? (int) $matches[1] : null;
                    $max = $matches[2] !== '' ? (int) $matches[2] : null;
                    abort_if(($min !== null && $min < 1) || ($max !== null && $max < 1), 404);
                    abort_if($min !== null && $max !== null && $min > $max, 404);
                    $filters[$key . '_min'] = $min;
                    $filters[$key . '_max'] = $max;
                    break;
                case 'beds':
                case 'baths':
                    abort_unless(preg_match('/^[1-9][0-9]?$/D', $value), 404);
                    $filters[$key] = (int) $value;
                    break;
                case 'ref':
                    abort_unless(preg_match('/^[1-9][0-9]*$/D', $value), 404);
                    $filters['ref'] = (int) $value;
                    break;
                case 'sort':
                    abort_unless(in_array($value, ['recent', 'price-low', 'price-high'], true), 404);
                    $filters['sort'] = $value;
                    break;
                case 'page':
                    abort_unless(preg_match('/^[1-9][0-9]{0,4}$/D', $value), 404);
                    $filters['page'] = (int) $value;
                    break;
                default:
                    abort(404);
            }
        }

        // Numeric comparisons across currencies are meaningless.
        abort_if(($filters['price_min'] !== null || $filters['price_max'] !== null) && $filters['currency'] === null, 404);

        return $filters;
    }

    public static function path(array $filters): string
    {
        $parts = [];

        if ($filters['deal']) {
            $parts[] = $filters['deal'];
        }

        if ($filters['location_type']) {
            array_push($parts, 'in', $filters['location_type'], ...$filters['location_slugs']);
        }

        if ($filters['type']) {
            array_push($parts, 'type', $filters['type']);
        }

        if ($filters['currency']) {
            array_push($parts, 'currency', $filters['currency']);
        }

        foreach (['price', 'area'] as $key) {
            if ($filters[$key . '_min'] !== null || $filters[$key . '_max'] !== null) {
                $parts[] = $key;
                $parts[] = ($filters[$key . '_min'] ?? '') . '-' . ($filters[$key . '_max'] ?? '');
            }
        }

        foreach (['beds', 'baths', 'ref'] as $key) {
            if ($filters[$key] !== null) {
                array_push($parts, $key, (string) $filters[$key]);
            }
        }

        if ($filters['offer']) {
            $parts[] = $filters['offer'];
        }

        if ($filters['sort'] !== 'recent') {
            array_push($parts, 'sort', $filters['sort']);
        }

        if ($filters['page'] > 1) {
            array_push($parts, 'page', (string) $filters['page']);
        }

        return implode('/', $parts);
    }

    public static function url(array $filters): string
    {
        $path = self::path($filters);

        return url('/realestate' . ($path !== '' ? '/' . $path : ''));
    }
}
