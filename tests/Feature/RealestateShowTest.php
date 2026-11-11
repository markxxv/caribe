<?php

namespace Tests\Feature;

use App\Models\Realestate\Realestate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealestateShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_realestate_is_publicly_visible_by_slug(): void
    {
        $property = Realestate::create([
            'title' => 'Casa de prueba',
            'active' => true,
            'price' => 250000,
            'currency' => 'USD',
            'covered_area' => 135,
        ]);

        $this->get(route('realestate.show', ['url' => $property->slug]))
            ->assertOk()
            ->assertSee('Casa de prueba')
            ->assertSee('250 000')
            ->assertSee('135')
            ->assertHeader('X-Robots-Tag', 'index, follow')
            ->assertDontSee('data-realestate-gallery');
    }

    public function test_draft_is_hidden_from_guests_but_available_to_logged_in_admin(): void
    {
        $property = Realestate::create([
            'title' => 'Черновик недвижимости',
            'active' => false,
        ]);

        $url = route('realestate.show', ['url' => $property->slug]);

        $this->get($url)->assertNotFound();

        $this->actingAs(User::factory()->create())
            ->get($url)
            ->assertOk()
            ->assertSee('Предпросмотр')
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_media_photos_are_rendered_as_a_gallery_with_thumbnails(): void
    {
        $property = Realestate::create(['title' => 'Casa con fotos', 'active' => true]);

        foreach (['primera.jpg', 'segunda.jpg'] as $name) {
            $property->media()->create([
                'collection_name' => 'photos',
                'name' => $name,
                'file_name' => $name,
                'mime_type' => 'image/jpeg',
                'disk' => 'public',
                'conversions_disk' => 'public',
                'size' => 1024,
                'manipulations' => [],
                'custom_properties' => [],
                'generated_conversions' => [],
                'responsive_images' => [],
            ]);
        }

        $this->get(route('realestate.show', ['url' => $property->slug]))
            ->assertOk()
            ->assertSee('data-realestate-gallery')
            ->assertSee('data-gallery-counter')
            ->assertSee('data-gallery-prev')
            ->assertSee('data-gallery-next')
            ->assertSee('data-gallery-thumb="0"', false)
            ->assertSee('data-gallery-thumb="1"', false)
            ->assertSee('primera.jpg')
            ->assertSee('segunda.jpg');
    }

    public function test_cover_is_used_if_no_photo_collection_exists(): void
    {
        $property = Realestate::create(['title' => 'Casa solo portada', 'active' => true]);

        $property->media()->create([
            'collection_name' => 'cover',
            'name' => 'portada.jpg',
            'file_name' => 'portada.jpg',
            'mime_type' => 'image/jpeg',
            'disk' => 'public',
            'conversions_disk' => 'public',
            'size' => 1024,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);

        $this->get(route('realestate.show', ['url' => $property->slug]))
            ->assertOk()
            ->assertSee('data-realestate-gallery')
            ->assertSee('portada.jpg')
            ->assertDontSee('data-gallery-thumb=');
    }

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/realestate/nonexistent-property')->assertNotFound();
    }
}
