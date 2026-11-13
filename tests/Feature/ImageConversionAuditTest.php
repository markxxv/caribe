<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\News;
use App\Models\PersonalBlog;
use App\Models\Realestate\Realestate;
use App\Models\Recipe;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageConversionAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_realestate_cover_conversions_resize_original_before_cropping(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp') || ! function_exists('imagecreatefromwebp')) {
            $this->markTestSkipped('GD with WebP support is required for conversion checks.');
        }

        Storage::fake(config('media-library.disk_name', 'public'));

        // A very wide image with recognizable colors to the left and right.
        // A direct crop(400,400) or crop(800,600) drops both color markers.
        $upload = UploadedFile::fake()->image('panorama.jpg', 1800, 1000);
        $canvas = imagecreatetruecolor(1800, 1000);
        $background = imagecolorallocate($canvas, 241, 241, 241);
        $red = imagecolorallocate($canvas, 235, 30, 30);
        $blue = imagecolorallocate($canvas, 30, 45, 235);
        imagefilledrectangle($canvas, 0, 0, 1799, 999, $background);
        imagefilledrectangle($canvas, 470, 200, 560, 800, $red);
        imagefilledrectangle($canvas, 1240, 200, 1330, 800, $blue);
        imagejpeg($canvas, $upload->getPathname(), 95);
        imagedestroy($canvas);

        $property = Realestate::create([
            'title' => 'A panorama with two landmarks',
            'active' => true,
            'sold' => false,
            'operation' => 'sale',
            'property_type' => 'house',
            'currency' => 'USD',
            'price' => 125000,
        ]);
        $media = $property->addMedia($upload)->toMediaCollection('cover');

        $dimensions = [
            'thumb' => [400, 400],
            'cover' => [780, 600],
            'gallery_thumb' => [580, 410],
        ];

        foreach ($dimensions as $conversion => $expected) {
            $this->assertTrue($media->hasGeneratedConversion($conversion), "Missing {$conversion} conversion.");
            $path = $media->getPath($conversion);
            $this->assertFileExists($path);
            $this->assertSame($expected, array_slice(getimagesize($path), 0, 2));
            $this->assertSame('image/webp', mime_content_type($path));
        }

        $this->assertBothSubjectsVisible($media->getPath('thumb'), 55, 345, 200);
        $this->assertBothSubjectsVisible($media->getPath('cover'), 160, 620, 300);
        $this->assertBothSubjectsVisible($media->getPath('gallery_thumb'), 130, 450, 205);
    }

    private function assertBothSubjectsVisible(string $file, int $leftX, int $rightX, int $y): void
    {
        $image = imagecreatefromwebp($file);
        $left = imagecolorsforindex($image, imagecolorat($image, $leftX, $y));
        $right = imagecolorsforindex($image, imagecolorat($image, $rightX, $y));

        $this->assertGreaterThan(150, $left['red'], 'Left landmark cropped out.');
        $this->assertLessThan(100, $left['blue']);
        $this->assertGreaterThan(150, $right['blue'], 'Right landmark cropped out.');
        $this->assertLessThan(100, $right['red']);
        imagedestroy($image);
    }

    public function test_blog_and_tour_builder_media_generate_proportional_webp(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD with WebP support is required.');
        }

        Storage::fake(config('media-library.disk_name', 'public'));

        foreach ([
            [Blog::class, 'blog_builder_image'],
            [Blog::class, 'blog_builder_gallery'],
            [Tour::class, 'tour_builder_image'],
            [Tour::class, 'tour_builder_gallery'],
        ] as [$modelClass, $collection]) {
            $record = $modelClass::create([
                'title' => 'Content with ' . $collection,
                'active' => true,
            ]);

            $media = $record->addMedia(UploadedFile::fake()->image('original.jpg', 1500, 900))
                ->toMediaCollection($collection);

            $this->assertTrue($media->hasGeneratedConversion('builder'), $collection);
            [$width, $height] = getimagesize($media->getPath('builder'));
            $this->assertSame(1200, $width, $collection);
            $this->assertSame(720, $height, $collection);
        }
    }

    public function test_editorial_galleries_do_not_force_portrait_crops(): void
    {
        foreach (['blog/show', 'content/show', 'tours/show'] as $view) {
            $source = file_get_contents(resource_path('views/' . $view . '.blade.php'));
            $this->assertStringNotContainsString('aspect-2/3', $source, $view);
            $this->assertStringContainsString('object-contain', $source, $view);
            $this->assertStringContainsString("getAvailableUrl(['builder'])", $source, $view);
        }

        $realestateSource = file_get_contents(resource_path('views/realestate/show.blade.php'));
        $this->assertStringContainsString("'gallery_thumb' : 'photo_thumb'", $realestateSource);
        $this->assertStringContainsString('object-contain sm:aspect-[16/10]', $realestateSource);
    }

    public function test_editorial_cover_conversions_preserve_aspect_ratio_outside_designed_3_by_2_cards(): void
    {
        foreach ([Tour::class, Blog::class, News::class, PersonalBlog::class, Recipe::class] as $model) {
            $source = file_get_contents(app_path('Models/' . class_basename($model) . '.php'));
            foreach (['thumb', 'cover', 'big', 'builder'] as $conversion) {
                $this->assertStringContainsString("addMediaConversion('{$conversion}')", $source, $model);
            }
            $this->assertStringContainsString('->fit(Fit::Crop, 600, 400)', $source, $model);
            $this->assertStringNotContainsString('->crop(', $source, $model);
        }
    }
}
