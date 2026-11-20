<?php

namespace Tests\Feature;

use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
use App\Models\Country;
use App\Models\Event;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_collection_can_be_created_and_reordered(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));
        $country = Country::create(['title' => 'Аргентина', 'slug' => 'argentina']);

        $items = [
            [
                'date' => '2026-11-21',
                'title' => 'Концерт',
                'announcement' => 'Вечер живой музыки.',
                'location' => 'Буэнос-Айрес',
                'map_url' => 'https://maps.app.goo.gl/example',
            ],
            [
                'date' => '2026-11-22',
                'title' => 'Выставка',
                'announcement' => 'Современное искусство.',
                'location' => 'Палермо',
                'map_url' => null,
            ],
        ];

        $undoRepeaterFake = Repeater::fake();

        try {
            Livewire::test(CreateEvent::class)
                ->fillForm([
                    'title' => 'Афиша ноября',
                    'country_id' => $country->id,
                    'active' => true,
                    'items' => $items,
                    'created_at' => '2026-11-12 10:30:00',
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $event = Event::sole();

            $this->assertTrue($event->active);
            $this->assertTrue($event->country->is($country));
            $this->assertEquals($items, array_map(fn (array $item, array $expected) => array_intersect_key($item, $expected), $event->items, $items));
            $itemSlugs = array_column($event->items, 'slug');
            $this->assertCount(2, array_unique($itemSlugs));
            $this->assertSame(Str::slug('Афиша ноября'), $event->slug);
            $this->assertSame('2026-11-12 10:30', $event->created_at->format('Y-m-d H:i'));

            Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
                ->fillForm([
                    'title' => 'Обновлённая афиша',
                    'items' => array_reverse($items),
                    'created_at' => '2026-11-13 11:45:00',
                ])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertEquals(array_reverse($items), array_map(fn (array $item, array $expected) => array_intersect_key($item, $expected), $event->refresh()->items, array_reverse($items)));
            $this->assertSame(array_reverse($itemSlugs), array_column($event->items, 'slug'));
            $this->assertSame(Str::slug('Афиша ноября'), $event->slug);
            $this->assertSame('2026-11-13 11:45', $event->created_at->format('Y-m-d H:i'));

            Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
                ->fillForm(['slug' => 'november-agenda'])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertSame('november-agenda', $event->refresh()->slug);
        } finally {
            $undoRepeaterFake();
        }
    }

    public function test_event_collection_requires_at_least_one_item(): void
    {
        $this->actingAs(User::factory()->create());
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));
        $country = Country::create(['title' => 'Аргентина', 'slug' => 'argentina']);

        Livewire::test(CreateEvent::class)
            ->fillForm(['title' => 'Афиша', 'country_id' => $country->id, 'items' => []])
            ->call('create')
            ->assertHasFormErrors(['items']);

        $this->assertDatabaseCount('events', 0);
    }

    public function test_public_agenda_is_sorted_and_contains_geographic_event_schema(): void
    {
        $this->withoutVite();

        $country = Country::create(['title' => 'Аргентина', 'slug' => 'argentina']);
        $event = $country->events()->create([
            'title' => 'Куда сходить в ноябре',
            'items' => [
                ['date' => '2026-11-28', 'title' => 'Позднее событие', 'announcement' => 'Выставка', 'location' => 'MALBA'],
                ['date' => '2026-11-21', 'title' => 'Раннее событие', 'announcement' => 'Концерт', 'location' => 'Teatro Colón', 'city' => 'Buenos Aires', 'street_address' => 'Cerrito 628', 'map_url' => 'https://maps.google.com/?q=Teatro+Colon', 'end_date' => '2026-11-22', 'start_time' => '19:00', 'end_time' => '21:00', 'performer' => 'Orchestra', 'performer_type' => 'PerformingGroup', 'price' => 0, 'currency' => 'ARS', 'ticket_url' => 'https://example.com/tickets', 'availability' => 'InStock'],
                ['date' => '2026-11-22', 'title' => 'Безопасная ссылка', 'location' => 'Буэнос-Айрес', 'map_url' => 'javascript:alert(1)'],
            ],
        ]);
        $draft = Event::create(['title' => 'Скрытая подборка', 'active' => false, 'items' => []]);

        $this->assertSame('ноября', $event->chronologicalItems()->first()['month']);
        $this->get(route('events.index'))
            ->assertOk()
            ->assertSee($event->title)
            ->assertDontSee($draft->title)
            ->assertSee('"@type":"ItemList"', false);

        $this->get($event->url)
            ->assertOk()
            ->assertSeeInOrder(['Раннее событие', 'Позднее событие'])
            ->assertSee('datetime="2026-11-21"', false)
            ->assertSee('ноября')
            ->assertSee('rel="canonical" href="' . $event->url . '"', false)
            ->assertDontSee('"@type":"Event"', false)
            ->assertDontSee('javascript:alert(1)', false);

        $item = $event->chronologicalItems()->first();
        $this->get($event->url)->assertSee($item['detail_url'], false);
        $this->get($item['detail_url'])
            ->assertOk()
            ->assertSee('rel="canonical" href="' . $item['detail_url'] . '"', false)
            ->assertSee('Раннее событие')
            ->assertSee('Cerrito 628')
            ->assertSee('Orchestra')
            ->assertSee('https://example.com/tickets')
            ->assertSee('"@type":"Event"', false)
            ->assertSee('"addressCountry":"Аргентина"', false)
            ->assertSee('"addressLocality":"Buenos Aires"', false)
            ->assertSee('"streetAddress":"Cerrito 628"', false)
            ->assertSee('"startDate":"2026-11-21T19:00:00"', false)
            ->assertSee('"endDate":"2026-11-22T21:00:00"', false)
            ->assertSee('"eventStatus":"https://schema.org/EventScheduled"', false)
            ->assertSee('"performer":{"@type":"PerformingGroup","name":"Orchestra"}', false)
            ->assertSee('"price":0,"priceCurrency":"ARS"', false)
            ->assertSee('"hasMap":"https://maps.google.com/?q=Teatro+Colon"', false);

        $incomplete = $event->chronologicalItems()->last();
        $this->get($incomplete['detail_url'])->assertOk()->assertDontSee('"@type":"Event"', false);
        $this->get($event->url . '/not-a-real-event')->assertNotFound();

        $this->get(route('events.show', ['slug' => $event->id]))->assertStatus(301)->assertRedirect($event->url);
        $this->get(route('events.show', ['slug' => $event->id . '-' . $event->slug]))->assertStatus(301)->assertRedirect($event->url);
        $this->assertSame(route('events.show', ['slug' => $event->slug]), $event->url);
        $this->get($draft->url)->assertNotFound();

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee(route('events.index'))
            ->assertSee($event->url)
            ->assertSee($item['detail_url'])
            ->assertDontSee($draft->url);
    }

    public function test_legacy_item_data_is_enriched_without_fabricating_offers(): void
    {
        $this->withoutVite();
        $country = Country::create(['title' => 'Аргентина', 'slug' => 'argentina']);
        $items = [
            [
                'date' => '2026-11-22',
                'title' => 'Sonorama — международный фестиваль винила',
                'announcement' => 'С 15:00 до 23:00.',
                'location' => 'Ciudad Cultural Konex, Sarmiento 3131',
                'city' => 'Edited city',
            ],
            [
                'date' => '2026-11-22',
                'title' => 'Ratha Yatra — фестиваль индийской культуры',
                'announcement' => 'С 12:00 до 20:00. Вход свободный.',
                'location' => 'Plaza Francia, Recoleta',
            ],
        ];

        $id = DB::table('events')->insertGetId([
            'title' => 'Куда сходить в Буэнос-Айресе в ноябре',
            'slug' => 'kuda-sxodit-v-buenos-airese-v-noiabre-2026',
            'active' => true,
            'items' => json_encode($items, JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_11_21_000000_enrich_existing_agenda_items.php');
        $migration->up();

        $event = Event::findOrFail($id);
        $this->assertSame($country->id, $event->country_id);
        $this->assertSame('Edited city', $event->items[0]['city']);
        $this->assertSame('Sarmiento 3131', $event->items[0]['street_address']);
        $this->assertSame('2026-11-22', $event->items[0]['end_date']);
        $this->assertArrayNotHasKey('price', $event->items[0]);
        $this->assertSame(0, $event->items[1]['price']);

        $page = $this->get($event->chronologicalItems()->first()['detail_url']);
        $page->assertOk()
            ->assertSee('"endDate":"2026-11-22T23:00:00"', false)
            ->assertDontSee('"offers":', false)
            ->assertDontSee('"performer":', false);
    }

    public function test_duplicate_event_titles_generate_unique_slugs(): void
    {
        $first = Event::create(['title' => 'Афиша ноября', 'items' => []]);
        $second = Event::create(['title' => 'Афиша ноября', 'items' => []]);

        $this->assertSame(Str::slug('Афиша ноября'), $first->slug);
        $this->assertNotSame($first->slug, $second->slug);
        $this->assertStringStartsWith($first->slug . '-', $second->slug);
    }
}
