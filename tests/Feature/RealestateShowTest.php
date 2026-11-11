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
            ->assertHeader('X-Robots-Tag', 'index, follow');
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

    public function test_unknown_slug_returns_404(): void
    {
        $this->get('/realestate/nonexistent-property')->assertNotFound();
    }
}
