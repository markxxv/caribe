<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\Events\EventDetails;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        $events = Event::query()->where('active', true)->with('country')->latest('id')->get();
        $canonical = route('events.index');
        $description = 'Афиша мероприятий в Латинской Америке: концерты, выставки, фестивали, ярмарки и интересные события.';
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'CollectionPage',
                    '@id' => $canonical . '#page',
                    'url' => $canonical,
                    'name' => 'Афиша мероприятий',
                    'description' => $description,
                    'inLanguage' => 'ru',
                    'isPartOf' => ['@id' => route('home') . '#website'],
                    'mainEntity' => ['@id' => $canonical . '#list'],
                ],
                [
                    '@type' => 'ItemList',
                    '@id' => $canonical . '#list',
                    'numberOfItems' => $events->count(),
                    'itemListElement' => $events->values()->map(fn (Event $event, int $index) => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $event->title,
                        'url' => $event->url,
                    ])->all(),
                ],
                $this->breadcrumbs($canonical),
            ],
        ];

        return view('events.index', compact('events', 'canonical', 'description', 'schema'));
    }

    public function show(string $slug): View|RedirectResponse
    {
        $record = Event::query()->with('country')->where('slug', $slug)->first();

        if (! $record) {
            // Previously published URLs used {id}-{title-slug}; retain their redirects.
            abort_unless(preg_match('/^(\d+)(?:-|$)/', $slug, $matches), 404);
            $legacy = Event::query()->where('active', true)->findOrFail($matches[1]);

            return redirect()->to($legacy->url, 301);
        }

        abort_unless($record->active, 404);

        $items = $record->chronologicalItems();
        $canonical = $record->url;
        $description = Str::limit($record->title . ': ' . ($items->isNotEmpty()
            ? $items->pluck('title')->implode(', ') . '. Даты и места проведения.'
            : 'Афиша и информация о мероприятиях.'), 180);

        $page = [
            '@type' => 'CollectionPage',
            '@id' => $canonical . '#page',
            'url' => $canonical,
            'name' => $record->title,
            'description' => $description,
            'inLanguage' => 'ru',
            'isPartOf' => ['@id' => route('home') . '#website'],
            'datePublished' => $record->created_at?->toAtomString(),
            'dateModified' => $record->updated_at?->toAtomString(),
            'mainEntity' => ['@id' => $canonical . '#list'],
        ];

        if ($record->country) {
            $page['about'] = ['@type' => 'Country', 'name' => $record->country->title];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                $page,
                [
                    '@type' => 'ItemList',
                    '@id' => $canonical . '#list',
                    'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
                    'numberOfItems' => $items->count(),
                    'itemListElement' => $items->map(fn (array $item, int $index) => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $item['title'],
                        'url' => $item['detail_url'],
                    ])->all(),
                ],
                $this->breadcrumbs($canonical, $record),
            ],
        ];

        return view('events.show', compact('record', 'items', 'canonical', 'description', 'schema'));
    }

    public function detail(string $slug, string $itemSlug): View
    {
        $record = Event::query()->where('active', true)->with('country')->where('slug', $slug)->firstOrFail();
        $item = $record->chronologicalItems()->firstWhere('slug', $itemSlug);
        abort_unless($item, 404);

        $canonical = $item['detail_url'];
        $description = Str::limit((string) ($item['announcement'] ?? $item['title']), 180);
        $details = EventDetails::data($record, $item);
        $breadcrumb = $this->breadcrumbs($canonical, $record);
        $breadcrumb['itemListElement'][2]['item'] = $record->url;
        $breadcrumb['itemListElement'][] = [
            '@type' => 'ListItem', 'position' => 4, 'name' => $item['title'], 'item' => $canonical,
        ];
        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [$breadcrumb],
        ];
        if ($node = EventDetails::schema($record, $item)) {
            $schema['@graph'][] = $node;
        }

        return view('events.detail', compact('record', 'item', 'canonical', 'description', 'details', 'schema'));
    }

    private function breadcrumbs(string $canonical, ?Event $event = null): array
    {
        $items = [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Главная', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Афиша мероприятий', 'item' => route('events.index')],
        ];

        if ($event) {
            $items[] = ['@type' => 'ListItem', 'position' => 3, 'name' => $event->title, 'item' => $canonical];
        }

        return ['@type' => 'BreadcrumbList', '@id' => $canonical . '#breadcrumb', 'itemListElement' => $items];
    }
}
