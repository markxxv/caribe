<?php

namespace Tests\Feature;

use App\Models\Tour;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotFoundPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_page_renders_custom_404_with_navigation_and_noindex(): void
    {
        $this->get('/definitely-not-a-real-page')
            ->assertNotFound()
            ->assertSee('Кажется, мы', false)
            ->assertSee('сбились с маршрута.')
            ->assertSee('На главную')
            ->assertSee('href="' . route('home') . '"', false)
            ->assertSee('href="' . route('tours.index') . '"', false)
            ->assertSee('href="' . route('blog.index') . '"', false)
            ->assertSee('name="robots" content="noindex, follow"', false);
    }

    public function test_404_shows_four_square_tour_cards_and_excludes_inactive_tours(): void
    {
        $prioritySlugs = [
            'istoricheskiy-siti-tur-po-buenos-ayresu',
            'tur-v-bariloche-s-reyndzherom-4kh4',
            'tur-po-severu-argentiny-khukhuy-tukuman-salta-katamarka',
            'tur-po-argentine-tropicheskaya-selva-vodopady-iguasu-i-iezuitskie-missii',
        ];

        foreach ($prioritySlugs as $index => $slug) {
            Tour::create([
                'title' => 'Рекомендованный маршрут ' . ($index + 1),
                'slug' => $slug,
                'active' => true,
            ]);
        }

        Tour::create(['title' => 'Скрытый тур', 'slug' => 'hidden-trip', 'active' => false]);
        Tour::create(['title' => 'Другой опубликованный тур', 'slug' => 'extra-trip', 'active' => true]);

        $response = $this->get('/unavailable-destination');
        $response->assertNotFound()
            ->assertSee('lg:grid-cols-4', false)
            ->assertSee('aspect-square', false)
            ->assertSee('object-cover', false)
            ->assertDontSee('Скрытый тур')
            ->assertDontSee('Другой опубликованный тур');

        $this->assertSame(4, substr_count($response->getContent(), 'data-404-tour-card'));

        foreach ($prioritySlugs as $slug) {
            $response->assertSee('href="' . route('tours.show', ['slug' => $slug]) . '"', false);
        }
    }

    public function test_square_conversion_is_regenerated_as_real_720_by_720_webp(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('The GD extension is required to generate an image fixture.');
        }

        Storage::fake(config('media-library.disk_name', 'public'));

        $tour = Tour::create(['title' => 'Wide tour image', 'active' => true]);
        $media = $tour->addMedia(UploadedFile::fake()->image('wide-tour.jpg', 1600, 900))
            ->toMediaCollection('tour_cover');

        $this->assertTrue($media->hasGeneratedConversion('square_card'));

        $imagePath = $media->getPath('square_card');
        $this->assertFileExists($imagePath);

        [$width, $height] = getimagesize($imagePath);
        $this->assertSame([720, 720], [$width, $height]);
        $this->assertSame('image/webp', mime_content_type($imagePath));
    }


    public function test_square_card_keeps_subjects_on_both_sides_of_a_wide_original(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is required.');
        }

        Storage::fake(config('media-library.disk_name', 'public'));

        // Two subjects near opposite sides of the largest possible 1:1 crop.
        // A fixed 720px source crop loses them both; crop-and-resize retains both.
        $photo = UploadedFile::fake()->image('two-subjects.jpg', 1800, 900);
        $canvas = imagecreatetruecolor(1800, 900);
        $white = imagecolorallocate($canvas, 245, 245, 245);
        $red = imagecolorallocate($canvas, 230, 30, 30);
        $blue = imagecolorallocate($canvas, 25, 55, 230);
        imagefilledrectangle($canvas, 0, 0, 1799, 899, $white);
        imagefilledrectangle($canvas, 465, 250, 515, 650, $red);
        imagefilledrectangle($canvas, 1285, 250, 1335, 650, $blue);
        imagejpeg($canvas, $photo->getPathname(), 95);
        imagedestroy($canvas);

        $tour = Tour::create(['title' => 'Wide landscape with two subjects', 'active' => true]);
        $media = $tour->addMedia($photo)->toMediaCollection('tour_cover');
        $this->assertTrue($media->hasGeneratedConversion('square_card'));

        $image = imagecreatefromwebp($media->getPath('square_card'));
        $this->assertSame(720, imagesx($image));
        $this->assertSame(720, imagesy($image));

        $left = imagecolorsforindex($image, imagecolorat($image, 35, 360));
        $right = imagecolorsforindex($image, imagecolorat($image, 685, 360));

        $this->assertGreaterThan(160, $left['red'], 'The left subject was cropped away.');
        $this->assertLessThan(100, $left['blue']);
        $this->assertGreaterThan(160, $right['blue'], 'The right subject was cropped away.');
        $this->assertLessThan(100, $right['red']);
        imagedestroy($image);
    }

    public function test_404_remains_helpful_without_published_tours(): void
    {
        Tour::create(['title' => 'Тур в черновике', 'active' => false]);

        $this->get('/not-found-anywhere')
            ->assertNotFound()
            ->assertSee('Перейти к турам')
            ->assertDontSee('data-404-tour-card', false);
    }
}
