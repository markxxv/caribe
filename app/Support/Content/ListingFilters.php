<?php

namespace App\Support\Content;

use App\Models\Country;
use Illuminate\Http\Request;

final class ListingFilters
{
    public static function parse(Request $request, bool $allowCountry = true, ?string $forcedCountry = null): array
    {
        $filters = ['country' => $forcedCountry, 'search' => null, 'page' => 1];
        $raw = (string) ($request->route('filters') ?? '');
        $parts = $raw === '' ? [] : explode('/', trim($raw, '/'));
        $seen = [];

        for ($i = 0; $i < count($parts);) {
            $key = $parts[$i++];
            abort_unless(in_array($key, ['country', 'search', 'page'], true) && ! isset($seen[$key]), 404);
            $seen[$key] = true;
            $value = $parts[$i++] ?? null;
            abort_if($value === null || $value === '', 404);

            if ($key === 'country') {
                abort_if(! $allowCountry || $forcedCountry !== null, 404);
                $filters['country'] = self::countrySlug($value);
            } elseif ($key === 'search') {
                $filters['search'] = self::searchTerm($value);
            } else {
                abort_unless(preg_match('/^[1-9][0-9]{0,4}$/D', $value), 404);
                $filters['page'] = (int) $value;
            }
        }

        // Legacy GET filter URLs always redirect to the corresponding path.
        if ($request->query->has('country')) {
            abort_unless($allowCountry, 404);
            $legacy = $request->query('country');
            abort_unless(is_string($legacy), 404);
            $filters['country'] = ($legacy === '' || $legacy === 'all')
                ? null
                : self::countrySlug($legacy);
        }

        if ($request->query->has('search')) {
            $legacy = $request->query('search');
            abort_unless(is_string($legacy), 404);
            $filters['search'] = $legacy === '' ? null : self::searchTerm($legacy);
        }

        if ($request->query->has('page')) {
            $legacy = $request->query('page');
            abort_unless(is_string($legacy) && preg_match('/^[1-9][0-9]{0,4}$/D', $legacy), 404);
            $filters['page'] = (int) $legacy;
        }

        return $filters;
    }

    public static function url(string $baseUrl, array $filters, ?int $page = null, bool $tours = false): string
    {
        $url = rtrim($baseUrl, '/');
        $country = $filters['country'] ?? null;

        if ($country !== null) {
            $url .= $tours && $country === 'argentina'
                ? '/argentina'
                : '/country/' . rawurlencode($country);
        }

        if (filled($filters['search'] ?? null)) {
            $url .= '/search/' . rawurlencode(self::searchTerm($filters['search']));
        }

        $page ??= (int) ($filters['page'] ?? 1);
        if ($page > 1) {
            $url .= '/page/' . $page;
        }

        return $url;
    }

    public static function canonicalRedirect(Request $request, string $target): ?\Illuminate\Http\RedirectResponse
    {
        $current = rawurldecode($request->getPathInfo());
        $expected = rawurldecode((string) parse_url($target, PHP_URL_PATH));

        if ($current !== $expected || $request->query->has('country') || $request->query->has('search') || $request->query->has('page')) {
            return redirect()->to($target, 301);
        }

        return null;
    }

    private static function countrySlug(string $value): string
    {
        if (ctype_digit($value)) {
            $slug = Country::query()->whereKey((int) $value)->value('slug');
            abort_unless($slug, 404);
            return $slug;
        }

        abort_unless(preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $value), 404);
        abort_unless(Country::query()->where('slug', $value)->exists(), 404);
        return $value;
    }

    private static function searchTerm(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', str_replace(['/', '\\'], ' ', rawurldecode($value))));
        abort_unless($value !== '' && mb_strlen($value) <= 120, 404);
        return $value;
    }
}
