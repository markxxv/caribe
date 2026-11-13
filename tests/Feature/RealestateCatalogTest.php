<?php

namespace Tests\Feature;

use App\Models\Realestate\City;
use App\Models\Realestate\Country;
use App\Models\Realestate\Realestate;
use App\Models\Realestate\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealestateCatalogTest extends TestCase
{
    use RefreshDatabase;

    private function property(string $title, array $values = []): Realestate
    {
        return Realestate::create(array_merge([
            'title' => $title,
            'active' => true,
            'sold' => false,
            'operation' => 'sale',
            'property_type' => 'house',
            'currency' => 'USD',
            'price' => 220000,
            'bedrooms' => 2,
            'bathrooms' => 1,
            'covered_area' => 110,
        ], $values));
    }

    public function test_catalog_is_public_and_excludes_drafts_and_sold_properties(): void
    {
        $this->property('Visible residence');
        $this->property('Hidden residence', ['active' => false]);
        $this->property('Sold residence', ['sold' => true]);

        $this->get('/realestate')
            ->assertOk()
            ->assertSee('Visible residence')
            ->assertDontSee('Hidden residence')
            ->assertDontSee('Sold residence')
            ->assertSee('data-catalog-search')
            ->assertHeader('X-Robots-Tag', 'index, follow');
    }

    public function test_friendly_urls_filter_by_operation_type_currency_price_and_features(): void
    {
        $this->property('Match house', ['newbuild' => true, 'bedrooms' => 3, 'bathrooms' => 2, 'price' => 350000]);
        $this->property('Too expensive', ['newbuild' => true, 'bedrooms' => 3, 'bathrooms' => 2, 'price' => 850000]);
        $this->property('Different currency', ['newbuild' => true, 'currency' => 'ARS', 'bedrooms' => 3, 'bathrooms' => 2, 'price' => 350000]);
        $this->property('Old house', ['newbuild' => false, 'bedrooms' => 3, 'bathrooms' => 2, 'price' => 350000]);

        $this->get('/realestate/buy/type/house/currency/USD/price/300000-500000/beds/3/baths/2/newbuild')
            ->assertOk()
            ->assertSee('Match house')
            ->assertDontSee('Too expensive')
            ->assertDontSee('Different currency')
            ->assertDontSee('Old house')
            ->assertHeader('X-Robots-Tag', 'noindex, follow');
    }

    public function test_geography_path_is_resolved_with_parent_slugs(): void
    {
        $country = Country::create(['name' => 'Argentina']);
        $region = Region::create(['name' => 'Buenos Aires', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Pinamar', 'region_id' => $region->id]);

        $this->property('Pinamar villa', [
            'country_id' => $country->id, 'region_id' => $region->id, 'city_id' => $city->id,
        ]);
        $this->property('Other villa');

        $this->get('/realestate/buy/in/city/argentina/buenos-aires/pinamar')
            ->assertOk()
            ->assertSee('Pinamar villa')
            ->assertDontSee('Other villa');

        $this->get('/realestate/in/city/argentina/unknown/pinamar')->assertNotFound();
    }

    public function test_invalid_segments_cannot_be_used_to_build_search_queries(): void
    {
        $this->get('/realestate/type/unknown')->assertNotFound();
        $this->get('/realestate/price/100-500')->assertNotFound();
        $this->get('/realestate/currency/USD/price/500-100')->assertNotFound();
        $this->get('/realestate/in/city/argentina')->assertNotFound();
    }

    public function test_noncanonical_order_redirects_and_property_slug_still_works(): void
    {
        $property = $this->property('Example home');
        $this->get('/realestate/type/house/buy')->assertRedirect('/realestate/buy/type/house');
        $this->get(route('realestate.show', ['url' => $property->slug]))->assertOk();
    }

    public function test_pagination_uses_paths_instead_of_query_strings(): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $this->property('Residence ' . $i);
        }

        $this->get('/realestate')->assertOk()
            ->assertSee('/realestate/page/2', false)
            ->assertDontSee('?page=2', false);

        $this->get('/realestate/page/2')->assertOk()->assertSee('Residence 1');
        $this->get('/realestate/page/3')->assertNotFound();
    }

    public function test_featured_property_does_not_steal_a_grid_slot_or_duplicate_across_pages(): void
    {
        for ($i = 1; $i <= 25; $i++) {
            $this->property('Residence ' . $i);
        }

        $page1 = $this->get('/realestate');
        $page1->assertOk()
            ->assertSee('Residence 25')
            ->assertSee('Residence 13')
            ->assertDontSee('Residence 12');

        // One editorial feature + a full, independent page of 12 cards.
        $this->assertSame(1, substr_count($page1->getContent(), 'data-featured-property'));
        $this->assertSame(12, substr_count($page1->getContent(), 'data-catalog-card'));

        $page2 = $this->get('/realestate/page/2');
        $page2->assertOk()
            ->assertSee('Residence 12')
            ->assertSee('Residence 1')
            ->assertDontSee('Residence 13')
            ->assertDontSee('Residence 25');

        $this->assertSame(0, substr_count($page2->getContent(), 'data-featured-property'));
        $this->assertSame(12, substr_count($page2->getContent(), 'data-catalog-card'));

        // The property total still includes the promoted record.
        $this->assertSame(25, Realestate::query()->count());
        $this->get('/realestate/page/3')->assertNotFound();

        // Filtered lists keep ordinary pagination without any special card.
        $filtered = $this->get('/realestate/buy');
        $filtered->assertOk();
        $this->assertSame(0, substr_count($filtered->getContent(), 'data-featured-property'));
        $this->assertSame(12, substr_count($filtered->getContent(), 'data-catalog-card'));
    }

    public function test_single_property_does_not_trigger_empty_state_when_featured(): void
    {
        $this->property('Only published property');

        $page = $this->get('/realestate');
        $page->assertOk()
            ->assertSee('Only published property')
            ->assertDontSee('По этим условиям пока нет объектов');

        $this->assertSame(1, substr_count($page->getContent(), 'data-featured-property'));
        $this->assertSame(0, substr_count($page->getContent(), 'data-catalog-card'));
    }

    public function test_tour_booking_widget_is_excluded_from_all_realestate_pages(): void
    {
        $property = $this->property('Widget free residence');

        $this->get('/contact')
            ->assertOk()
            ->assertSee('aria-label="Заказать экскурсию в WhatsApp"', false);

        $this->get('/realestate')
            ->assertOk()
            ->assertDontSee('Заказать экскурсию в WhatsApp');

        $this->get('/realestate/buy')
            ->assertOk()
            ->assertDontSee('Заказать экскурсию в WhatsApp');

        $this->get(route('realestate.show', ['url' => $property->slug]))
            ->assertOk()
            ->assertDontSee('Заказать экскурсию в WhatsApp');
    }

    public function test_mobile_menu_has_no_broken_logo_or_unregistered_about_link(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('id="site-mobile-menu"', false)
            ->assertSee('text-[32px]', false)
            ->assertDontSee('src="/img/logo.png"', false)
            ->assertDontSee('href="/about"', false);
    }

    public function test_search_dropdowns_have_opaque_background_scroll_and_overlay_styles(): void
    {
        $this->get('/realestate')
            ->assertOk()
            ->assertSee('max-h-[340px] overflow-y-auto rounded-xl bg-white p-2', false)
            ->assertSee('hidden rounded-xl bg-white p-4 shadow-', false)
            ->assertDontSee('lg:top-full" overflow-y-auto', false)
            ->assertDontSee('lg:top-full" bg-white', false);
    }

}
