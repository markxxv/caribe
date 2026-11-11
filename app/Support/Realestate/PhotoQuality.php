<?php

namespace App\Support\Realestate;

class PhotoQuality
{
    /**
     * Source URLs describe the actual CDN variant downloaded by the importer.
     * A 360x266 source cannot be improved by generating a 1600px WebP.
     */
    public static function isUpgrade(string $candidate, ?string $current): bool
    {
        if (blank($current)) {
            return true;
        }

        if ($candidate === $current) {
            return false;
        }

        return self::score($candidate) > self::score($current);
    }

    private static function score(string $url): int
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (! preg_match('~/(\d{2,4})x(\d{2,4})/~', $path, $matches)) {
            return 0;
        }

        // Prefer the direct CDN source over its /resize/ proxy at equal size.
        return ((int) $matches[1] * (int) $matches[2] * 2)
            + (str_contains($path, '/avisos/resize/') ? 0 : 1);
    }
}
