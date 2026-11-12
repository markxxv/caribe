<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ToursLandingSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_tours_page_targets_argentina_and_renders_the_authors_text(): void
    {
        $this->get('/tours')
            ->assertOk()
            ->assertSee('Индивидуальные туры по Аргентине с гидом Еленой Вивас | Visita Caribe')
            ->assertSee('Индивидуальные туры с гидом по Аргентине')
            ->assertSee('Составляю индивидуальные туры по Аргентине под даты и длительность вашего путешествия, состав группы и главное - ваши интересы.')
            ->assertSee('Знаю лучше отели и частные ранчо')
            ->assertSee('Что входит в индивидуальный тур')
            ->assertSee('Как строится работа')
            ->assertSee('Я подготовлю программу которая подходит именно вам.')
            ->assertSee('img/guide_elena.png')
            ->assertSee('https://wa.me/34660024093')
            ->assertSee('rel="canonical" href="http://localhost/tours"', false)
            ->assertSee('"@type":"CollectionPage"', false)
            ->assertSee('"@type":"ItemList"', false)
            ->assertSee('"@type":"Person"', false)
            ->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_editorial_links_point_to_real_tours_without_changing_visible_place_names(): void
    {
        $this->get('/tours')
            ->assertOk()
            ->assertSee('href="/tours/istoricheskiy-siti-tur-po-buenos-ayresu">Буэнос-Айрес</a>', false)
            ->assertSee('href="/tours/tur-po-severu-argentiny-khukhuy-tukuman-salta-katamarka">Сальта, Хухуй, Тукуман</a>', false)
            ->assertSee('href="/tours/tur-v-bariloche-s-reyndzherom-4kh4">Барилоче с рейнджером</a>', false)
            ->assertSee('href="/tours/tur-po-argentine-tropicheskaya-selva-vodopady-iguasu-i-iezuitskie-missii">Игуасу и тропическую зону</a>', false);
    }

    public function test_argentina_category_has_its_own_canonical_without_duplicate_editorial(): void
    {
        $this->get('/tours/argentina')
            ->assertOk()
            ->assertSee('rel="canonical" href="http://localhost/tours/argentina"', false)
            ->assertSee('Туры по Аргентине с русскоязычным гидом | Visita Caribe')
            ->assertDontSee('Индивидуальные туры с гидом по Аргентине');
    }

    public function test_filtered_tour_results_are_not_indexed_as_duplicate_landings(): void
    {
        $this->get('/tours?country=all')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, follow"', false)
            ->assertDontSee('Индивидуальные туры с гидом по Аргентине')
            ->assertDontSee('application/ld+json');
    }
}
