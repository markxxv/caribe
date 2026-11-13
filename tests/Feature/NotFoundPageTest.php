<?php

namespace Tests\Feature;

use App\Models\Tour;
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
            ->assertSee('object-cover object-top', false)
            ->assertDontSee('Скрытый тур')
            ->assertDontSee('Другой опубликованный тур');

        $this->assertSame(4, substr_count($response->getContent(), 'data-404-tour-card'));

        foreach ($prioritySlugs as $slug) {
            $response->assertSee('href="' . route('tours.show', ['slug' => $slug]) . '"', false);
        }
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
