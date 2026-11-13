<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Tour;
use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorialGalleryLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_photo_gallery_places_last_image_across_both_columns(): void
    {
        $this->assertGalleryLayout(3, 1);
    }

    public function test_four_photo_gallery_uses_two_by_two_grid_without_wide_image(): void
    {
        $this->assertGalleryLayout(4, 0);
    }

    public function test_tours_and_news_use_the_same_editorial_gallery_component(): void
    {
        foreach ([[Tour::class, 'tours.show'], [News::class, 'news.show']] as [$model, $route]) {
            $record = $this->withGallery($model, 3);
            $this->get(route($route, ['slug' => $record->slug]))
                ->assertOk()
                ->assertSee('data-editorial-gallery', false)
                ->assertSee('data-gallery-span="wide"', false);
        }
    }

    private function assertGalleryLayout(int $count, int $wideItems): void
    {
        $record = $this->withGallery(Blog::class, $count);
        $response = $this->get(route('blog.show', ['slug' => $record->slug]));

        $response->assertOk()
            ->assertSee('data-editorial-gallery', false)
            ->assertSee('sm:grid-cols-2', false)
            ->assertSee('sm:aspect-[4/5]', false);

        $this->assertSame($count, substr_count($response->getContent(), 'data-editorial-gallery-item'));
        $this->assertSame($wideItems, substr_count($response->getContent(), 'data-gallery-span="wide"'));

        if ($wideItems) {
            $response->assertSee('sm:col-span-2', false)
                ->assertSee('sm:aspect-[3/2]', false);
        } else {
            $response->assertDontSee('data-gallery-span="wide"', false);
        }
    }

    private function withGallery(string $model, int $count): Blog|Tour|News
    {
        $record = $model::create(['title' => 'Снимки из Барилоче', 'active' => true]);
        $ids = [];

        for ($i = 1; $i <= $count; $i++) {
            $media = $record->media()->create([
                'collection_name' => $record::mediaPrefix() . '_builder_gallery',
                'name' => 'gallery-' . $i,
                'file_name' => 'gallery-' . $i . '.jpg',
                'mime_type' => 'image/jpeg',
                'disk' => 'public',
                'conversions_disk' => 'public',
                'size' => 1024,
                'manipulations' => [],
                'custom_properties' => [],
                'generated_conversions' => [],
                'responsive_images' => [],
            ]);
            $ids[] = $media->id;
        }

        $record->update(['content' => [
            [
                'type' => 'gallery',
                'data' => ['images' => $ids, 'columns' => 'columns-2'],
            ],
        ]]);

        return $record;
    }
}
