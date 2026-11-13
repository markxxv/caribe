<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToursCountryAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_argentina_alias_has_filtered_tours_and_dedicated_seo(): void
    {
        $argentina = Country::create(['title' => 'Аргентина', 'slug' => 'argentina']);
        $chile = Country::create(['title' => 'Чили', 'slug' => 'chile']);

        $argentinaTour = Tour::create([
            'title' => 'Поездка в Барилоче',
            'slug' => 'bariloche-test',
            'active' => true,
        ]);
        $argentinaTour->countries()->attach($argentina->id);

        $chileTour = Tour::create([
            'title' => 'Поездка в Сантьяго',
            'slug' => 'santiago-test',
            'active' => true,
        ]);
        $chileTour->countries()->attach($chile->id);

        $this->get('/tours/argentina')
            ->assertOk()
            ->assertSee('Туры по Аргентине')
            ->assertSee('Поездка в Барилоче')
            ->assertDontSee('Поездка в Сантьяго')
            ->assertSee('rel="canonical"', false)
            ->assertSee('value="argentina" selected', false);
    }

    public function test_legacy_argentina_filter_redirects_permanently_to_alias(): void
    {
        $this->get('/tours?search=&country=1')
            ->assertStatus(301)
            ->assertRedirect('/tours/argentina');
    }

    public function test_other_country_filter_still_works(): void
    {
        $chile = Country::create(['title' => 'Чили', 'slug' => 'chile']);

        $chileTour = Tour::create([
            'title' => 'Тур по Чили',
            'slug' => 'tour-chile-test',
            'active' => true,
        ]);
        $chileTour->countries()->attach($chile->id);

        $this->get('/tours?country=' . $chile->id)
            ->assertStatus(301)
            ->assertRedirect('/tours/country/chile');

        $this->get('/tours/country/chile')
            ->assertOk()
            ->assertSee('Тур по Чили');
    }
}
