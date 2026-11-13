<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\News;
use App\Models\PersonalBlog;
use App\Models\Recipe;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RelatedRecordsBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_related_records_share_compact_square_layout_in_blog_tours_and_news(): void
    {
        $relatedTour = Tour::create([
            'title' => 'Тур в Барилоче с рейнджером',
            'active' => true,
        ]);
        $hiddenTour = Tour::create([
            'title' => 'Снятый с публикации тур',
            'active' => false,
        ]);

        $block = [
            'type' => 'attach_record',
            'data' => [
                'title' => 'Посетить Барилоче',
                'items' => [
                    ['model' => Tour::class, 'record_id' => $relatedTour->id],
                    ['model' => Tour::class, 'record_id' => $hiddenTour->id],
                ],
            ],
        ];

        foreach ([
            [Blog::class, 'blog.show'],
            [Tour::class, 'tours.show'],
            [News::class, 'news.show'],
        ] as [$model, $route]) {
            $page = $model::create([
                'title' => 'Страница с авторскими маршрутами — ' . $route,
                'active' => true,
                'content' => [$block],
            ]);

            $response = $this->get(route($route, ['slug' => $page->slug]));
            $response->assertOk()
                ->assertSee('Посетить Барилоче')
                ->assertSee('data-related-records', false)
                ->assertSee('max-w-3xl', false)
                ->assertSee('flex flex-col gap-3', false)
                ->assertDontSee('md:grid-cols-2', false)
                ->assertSee('size-24', false)
                ->assertSee('Тур в Барилоче с рейнджером')
                ->assertSee('href="' . $relatedTour->url . '"', false)
                ->assertDontSee('Снятый с публикации тур');

            $this->assertSame(1, substr_count($response->getContent(), 'data-related-record-item'));
        }
    }

    public function test_square_conversion_is_available_for_all_editorial_record_types(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagewebp')) {
            $this->markTestSkipped('GD and WebP support are needed for conversion tests.');
        }

        Storage::fake(config('media-library.disk_name', 'public'));

        foreach ([Tour::class, Blog::class, News::class, PersonalBlog::class, Recipe::class] as $model) {
            $record = $model::create(['title' => 'Cover for ' . class_basename($model), 'active' => true]);
            $media = $record->addMedia(
                UploadedFile::fake()->image('wide-cover.jpg', 1500, 1000)
            )->toMediaCollection($record::mediaPrefix() . '_cover');

            $this->assertTrue($media->hasGeneratedConversion('square_card'), $model);
            $file = $media->getPath('square_card');

            $this->assertFileExists($file, $model);
            $this->assertSame([720, 720], array_slice(getimagesize($file), 0, 2), $model);
            $this->assertSame('image/webp', mime_content_type($file), $model);
        }
    }

    public function test_related_records_component_does_not_use_legacy_cards(): void
    {
        $component = file_get_contents(resource_path('views/components/editorial/related-records.blade.php'));
        $this->assertStringContainsString("getAvailableUrl(['square_card', 'cover'])", $component);
        $this->assertStringNotContainsString('bg-gray-100', $component);
        $this->assertStringNotContainsString('border-b', $component);
        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('.prose [data-related-records] a[data-related-record-item]', $css);
        $this->assertStringContainsString('border-bottom: 0;', $css);

        foreach (['blog/show', 'tours/show', 'content/show'] as $page) {
            $blade = file_get_contents(resource_path('views/' . $page . '.blade.php'));
            $this->assertStringContainsString('<x-editorial.related-records :block="$block" />', $blade);
        }
    }
}
