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
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $event = Event::sole();

            $this->assertTrue($event->active);
            $this->assertTrue($event->country->is($country));
            $this->assertEquals($items, $event->items);

            Livewire::test(EditEvent::class, ['record' => $event->getRouteKey()])
                ->fillForm(['items' => array_reverse($items)])
                ->call('save')
                ->assertHasNoFormErrors();

            $this->assertEquals(array_reverse($items), $event->refresh()->items);
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
                ['date' => '2026-11-21', 'title' => 'Раннее событие', 'announcement' => 'Концерт', 'location' => 'Teatro Colón', 'map_url' => 'https://maps.google.com/?q=Teatro+Colon'],
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
            ->assertSee('"@type":"Event"', false)
            ->assertSee('"addressCountry":"Аргентина"', false)
            ->assertSee('"startDate":"2026-11-21"', false)
            ->assertSee('"hasMap":"https://maps.google.com/?q=Teatro+Colon"', false)
            ->assertDontSee('javascript:alert(1)', false);

        $this->get(route('events.show', ['event' => $event->id]))->assertStatus(301)->assertRedirect($event->url);
        $this->get($draft->url)->assertNotFound();

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee(route('events.index'))
            ->assertSee($event->url)
            ->assertDontSee($draft->url);
    }
}
