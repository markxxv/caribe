<?php

namespace Tests\Feature;

use App\Models\Realestate\City;
use App\Models\Realestate\Country;
use App\Models\Realestate\Realestate;
use App\Models\Realestate\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealestateCityPageTest extends TestCase
{
    use RefreshDatabase;

    private function city(array $attributes = []): City
    {
        $country = Country::create(['name' => 'Argentina']);
        $region = Region::create(['name' => 'Buenos Aires', 'country_id' => $country->id]);

        return City::create(array_merge([
            'name' => 'Pinamar',
            'region_id' => $region->id,
        ], $attributes));
    }

    private function property(City $city, string $title, array $attributes = []): Realestate
    {
        return Realestate::create(array_merge([
            'title' => $title,
            'city_id' => $city->id,
            'country_id' => $city->region->country_id,
            'region_id' => $city->region_id,
            'active' => true,
            'sold' => false,
            'operation' => 'sale',
            'price' => 100000,
            'currency' => 'USD',
        ], $attributes));
    }

    public function test_city_has_separate_public_editorial_page_with_description_and_seo(): void
    {
        $city = $this->city([
            'description' => '<h2>Знакомство с городом</h2><p>Сосновые леса и океан.</p>',
            'meta_title' => 'Пинамар: недвижимость и жизнь',
            'meta_description' => 'Описание Пинамара для поисковых систем.',
        ]);

        $this->property($city, 'Светлая вилла');
        $this->property($city, 'Проданная вилла', ['sold' => true]);
        $this->property($city, 'Арендная вилла', ['operation' => 'rent']);
        $this->property($city, 'Черновик виллы', ['active' => false]);

        $this->assertSame('/realestate/city/argentina/buenos-aires/pinamar', parse_url($city->publicUrl(), PHP_URL_PATH));

        $this->get($city->publicUrl())
            ->assertOk()
            ->assertSee('Знакомство с городом')
            ->assertSee('Сосновые леса и океан.')
            ->assertSee('Пинамар: недвижимость и жизнь')
            ->assertSee('Описание Пинамара для поисковых систем.')
            ->assertSee('Светлая вилла')
            ->assertSee('Дома и виллы')
            ->assertSee('Квартиры')
            ->assertSee('/realestate/buy/in/city/argentina/buenos-aires/pinamar/type/house', false)
            ->assertSee('/realestate/buy/in/city/argentina/buenos-aires/pinamar/type/apartment', false)
            ->assertSee('Пинамар: недвижимость и жизнь')
            ->assertSee('relative isolate overflow-hidden', false)
            ->assertDontSee('aria-label="Навигация"', false)
            ->assertDontSee('Арендовать')
            ->assertDontSee('Арендная вилла')
            ->assertDontSee('Проданная вилла')
            ->assertDontSee('Черновик виллы')
            ->assertHeader('X-Robots-Tag', 'index, follow');
    }

    public function test_meta_title_subtitle_is_hidden_when_city_has_no_meta_title(): void
    {
        $city = $this->city(['description' => '<p>Город у океана.</p>']);

        $response = $this->get($city->publicUrl());

        $response->assertOk()
            ->assertSee('Город у океана.')
            ->assertDontSee('class="mt-4 max-w-[750px] text-sm leading-7 text-white/85 sm:text-base"', false);
    }

    public function test_private_city_cover_is_served_inline_without_exposing_disk_path(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $city = $this->city();
        $media = $city->media()->create([
            'collection_name' => 'cover',
            'name' => 'city',
            'file_name' => 'city.png',
            'mime_type' => 'image/png',
            'disk' => 'local',
            'conversions_disk' => 'local',
            'size' => 68,
            'manipulations' => [],
            'custom_properties' => [],
            'generated_conversions' => [],
            'responsive_images' => [],
        ]);
        \Illuminate\Support\Facades\Storage::disk('local')->put($media->getPathRelativeToRoot(), base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO2Qj3sAAAAASUVORK5CYII='
        ));

        $this->get($city->publicUrl())
            ->assertOk()
            ->assertSee($city->publicCoverUrl(), false)
            ->assertDontSee('/storage/' . $media->getKey(), false);

        $this->get($city->publicCoverUrl())
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_city_url_requires_matching_parent_country_and_region(): void
    {
        $city = $this->city(['description' => '<p>О Пинамаре</p>']);

        $this->get($city->publicUrl())->assertOk();
        $this->get('/realestate/city/argentina/unknown/pinamar')->assertNotFound();
        $this->get('/realestate/city/uruguay/buenos-aires/pinamar')->assertNotFound();
        $this->get('/realestate/city/argentina/buenos-aires/missing')->assertNotFound();
    }

    public function test_city_listing_uses_path_pagination_and_excludes_unpublished_properties(): void
    {
        $city = $this->city(['description' => '<p>Город у океана</p>']);

        for ($i = 1; $i <= 11; $i++) {
            $this->property($city, 'Дом ' . $i);
        }

        $this->get($city->publicUrl())
            ->assertOk()
            ->assertSee('/realestate/city/argentina/buenos-aires/pinamar/page/2', false)
            ->assertDontSee('?page=2', false);

        $this->get($city->publicUrl() . '/page/2')
            ->assertOk()
            ->assertSee('Дом 1')
            ->assertHeader('X-Robots-Tag', 'noindex, follow');

        $this->get($city->publicUrl() . '/page/3')->assertNotFound();
        $this->get($city->publicUrl() . '/page/1')->assertNotFound();
    }

    public function test_other_cities_start_with_current_region_and_include_other_regions_without_descriptions(): void
    {
        $city = $this->city(['description' => '<p>Город у реки</p>']);
        $sameRegion = City::create(['name' => 'Zarate', 'region_id' => $city->region_id]);
        $otherRegion = Region::create([
            'name' => 'Cordoba',
            'country_id' => $city->region->country_id,
        ]);
        $outside = City::create(['name' => 'Avellaneda', 'region_id' => $otherRegion->id]);

        $response = $this->get($city->publicUrl());

        $response->assertOk()
            ->assertSee('Другие города')
            ->assertSee($sameRegion->publicUrl(), false)
            ->assertSee($outside->publicUrl(), false)
            ->assertSeeInOrder(['Zarate', 'Avellaneda']);
    }

    public function test_empty_unwritten_city_is_noindex_and_described_city_is_in_sitemap(): void
    {
        $city = $this->city();
        $this->get($city->publicUrl())
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, follow');

        $this->get('/sitemap.xml')->assertDontSee($city->publicUrl());

        $city->update(['description' => '<p>Жизнь на побережье</p>']);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee($city->publicUrl(), false);
    }
}
