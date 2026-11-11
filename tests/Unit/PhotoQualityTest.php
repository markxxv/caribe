<?php

namespace Tests\Unit;

use App\Support\Realestate\PhotoQuality;
use PHPUnit\Framework\TestCase;

class PhotoQualityTest extends TestCase
{
    public function test_larger_cdn_source_upgrades_a_saved_thumbnail(): void
    {
        $small = 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/360x266/2046926477.jpg';
        $large = 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/1200x1200/2046926477.jpg';

        $this->assertTrue(PhotoQuality::isUpgrade($large, $small));
        $this->assertFalse(PhotoQuality::isUpgrade($small, $large));
        $this->assertFalse(PhotoQuality::isUpgrade($large, $large));
    }

    public function test_direct_cdn_url_is_preferred_to_resize_at_same_dimensions(): void
    {
        $resize = 'https://imgar.zonapropcdn.com/avisos/resize/1/00/58/88/45/69/1200x1200/2046926477.jpg';
        $direct = 'https://imgar.zonapropcdn.com/avisos/1/00/58/88/45/69/1200x1200/2046926477.jpg';

        $this->assertTrue(PhotoQuality::isUpgrade($direct, $resize));
        $this->assertFalse(PhotoQuality::isUpgrade($resize, $direct));
    }
}
