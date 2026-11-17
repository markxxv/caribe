<?php

namespace Tests\Feature;

use App\Filament\Resources\Events\Pages\CreateEvent;
use App\Filament\Resources\Events\Pages\EditEvent;
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
                    'active' => true,
                    'items' => $items,
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $event = Event::sole();

            $this->assertTrue($event->active);
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

        Livewire::test(CreateEvent::class)
            ->fillForm(['title' => 'Афиша', 'items' => []])
            ->call('create')
            ->assertHasFormErrors(['items']);

        $this->assertDatabaseCount('events', 0);
    }
}
