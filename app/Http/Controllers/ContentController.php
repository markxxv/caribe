<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\News;
use App\Models\PersonalBlog;
use App\Models\Recipe;
use Illuminate\Http\Request;

/**
 * Публичные страницы новостей, личных статей (эмиграция) и рецептов.
 * URL повторяют старый сайт на Craft: /news, /articles/emigratsiya, /article/{slug}, /recipes, /recipes/{slug}.
 */
class ContentController extends Controller
{
    private const TYPES = [
        'news' => [
            'model' => News::class,
            'title' => 'Новости',
            'heading' => 'Новости Латинской Америки',
            'lead' => 'Актуальные новости из официальных СМИ стран Латинской Америки на русском языке',
            'metaTitle' => 'Новости Аргентины и Латинской Америки на русском языке | Visita Caribe',
            'metaDescription' => 'Актуальные новости из официальных СМИ стран Латинской Америки на русском языке.',
            'unit' => 'новостей',
            'found' => 'Найдено новостей',
            'empty' => 'Новостей не найдено',
            'fallbackDesc' => 'новость из Латинской Америки.',
            'countryRelation' => 'news',
        ],
        'article' => [
            'model' => PersonalBlog::class,
            'title' => 'Эмиграция',
            'heading' => 'Эмиграция в Латинскую Америку',
            'lead' => 'Практические статьи о переезде, жизни, финансах и недвижимости',
            'metaTitle' => 'Эмиграция в Латинскую Америку | Visita Caribe',
            'metaDescription' => 'Статьи об эмиграции в Латинскую Америку: экономика, банковская система, недвижимость и жизнь в стране.',
            'unit' => 'статей',
            'found' => 'Найдено статей',
            'empty' => 'Статей не найдено',
            'fallbackDesc' => 'статья об эмиграции в Латинскую Америку.',
            'countryRelation' => 'personalBlogs',
        ],
        'recipes' => [
            'model' => Recipe::class,
            'title' => 'Рецепты',
            'heading' => 'Рецепты Латинской Америки',
            'lead' => 'Кухня Латинской Америки: блюда, выпечка, напитки и праздничные рецепты',
            'metaTitle' => 'Рецепты кухни Латинской Америки | Visita Caribe',
            'metaDescription' => 'Рецепты кухни Латинской Америки с пошаговыми описаниями и фотографиями от Елены Вивас.',
            'unit' => 'рецептов',
            'found' => 'Найдено рецептов',
            'empty' => 'Рецептов не найдено',
            'fallbackDesc' => 'рецепт кухни Латинской Америки.',
            'countryRelation' => null,
        ],
    ];

    public function index(Request $request)
    {
        // Параметры маршрута читаем по имени: Laravel передаёт их в метод позиционно.
        $type = (string) $request->route('type');
        $category = $request->route('category');
        $cfg = $this->config($type);
        $model = $cfg['model'];

        $query = $model::query()->where('active', true)->with('media');
        if ($cfg['countryRelation']) {
            $query->with('countries');
        }

        $categoryTitle = null;
        if ($category !== null) {
            $categoryTitle = PersonalBlog::query()->where('category_slug', $category)->value('category_name');
            abort_if($type !== 'article' || ! $categoryTitle, 404);
            $query->where('category_slug', $category);
        }

        if ($cfg['countryRelation'] && $request->filled('country') && $request->country !== 'all') {
            $query->whereHas('countries', fn ($q) => $q->where('countries.id', (int) $request->country));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        $items = $query->orderByRaw('COALESCE(published_at, created_at) DESC')->paginate(12);

        $countries = $cfg['countryRelation']
            ? Country::query()
                ->whereHas($cfg['countryRelation'], fn ($q) => $q->where('active', true))
                ->orderBy('title')
                ->get()
            : collect();

        $indexUrl = $this->indexUrl($type, $category);
        $heading = $categoryTitle ? $categoryTitle : $cfg['heading'];

        return view('content.index', compact('items', 'countries', 'type', 'cfg', 'indexUrl', 'heading'));
    }

    public function show(Request $request)
    {
        $type = (string) $request->route('type');
        $slug = (string) $request->route('slug');
        $cfg = $this->config($type);
        $model = $cfg['model'];

        $item = $model::query()
            ->where('slug', $slug)
            ->where('active', true)
            ->with('media')
            ->firstOrFail();

        if ($cfg['countryRelation']) {
            $item->load('countries');
        }

        $related = $model::query()
            ->where('active', true)
            ->whereKeyNot($item->getKey())
            ->with('media')
            ->inRandomOrder()
            ->limit(6)
            ->get();

        $indexUrl = $this->indexUrl($type, null);

        return view('content.show', compact('item', 'type', 'cfg', 'indexUrl', 'related'));
    }

    private function config(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    private function indexUrl(string $type, ?string $category): string
    {
        return $category
            ? route("{$type}.index", ['category' => $category])
            : route("{$type}.index");
    }
}
