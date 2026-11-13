<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\News;
use App\Models\PersonalBlog;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use Illuminate\Http\Request;
use App\Support\Content\ListingFilters;

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
        $type = (string) $request->route('type');
        $category = $request->route('category');
        $cfg = $this->config($type);
        $model = $cfg['model'];

        $categoryTitle = null;
        $activeCategory = null;
        if ($category !== null && $type === 'recipes') {
            $activeCategory = RecipeCategory::query()
                ->where('active', true)
                ->where('slug', $category)
                ->firstOrFail();
            $categoryTitle = $activeCategory->title;
        } elseif ($category !== null) {
            abort_unless($type === 'article', 404);
            $categoryTitle = PersonalBlog::query()
                ->where('category_slug', $category)
                ->value('category_name');
            abort_unless($categoryTitle, 404);
        }

        $indexUrl = $this->indexUrl($type, $category);
        $listingFilters = ListingFilters::parse($request, (bool) $cfg['countryRelation']);
        if ($redirect = ListingFilters::canonicalRedirect(
            $request, ListingFilters::url($indexUrl, $listingFilters)
        )) {
            return $redirect;
        }

        $query = $model::query()->where('active', true)->with('media');
        if ($cfg['countryRelation']) {
            $query->with('countries');
        }
        if ($activeCategory) {
            $query->where('recipe_category_id', $activeCategory->id);
        } elseif ($category !== null) {
            $query->where('category_slug', $category);
        }

        if ($listingFilters['country']) {
            $query->whereHas('countries', fn ($q) =>
                $q->where('countries.slug', $listingFilters['country'])
            );
        }
        if ($listingFilters['search']) {
            $term = $listingFilters['search'];
            $query->where(fn ($q) =>
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('meta_description', 'like', "%{$term}%")
            );
        }

        $items = $query->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->paginate(12, ['*'], 'page', $listingFilters['page']);

        $countries = $cfg['countryRelation']
            ? Country::query()
                ->whereHas($cfg['countryRelation'], fn ($q) => $q->where('active', true))
                ->orderBy('title')
                ->get()
            : collect();

        $heading = $categoryTitle ?: $cfg['heading'];
        $recipeCategories = $type === 'recipes' ? $this->recipeCategories() : collect();

        return view('content.index', compact(
            'items', 'countries', 'type', 'cfg', 'indexUrl', 'heading', 'listingFilters',
            'recipeCategories', 'activeCategory'
        ));
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

    /**
     * Опубликованные категории рецептов, у которых есть хотя бы один опубликованный рецепт.
     * Обложка категории — обложка самого свежего рецепта, у которого она есть.
     */
    private function recipeCategories()
    {
        $categories = RecipeCategory::query()
            ->where('active', true)
            ->whereHas('recipes', fn ($q) => $q->where('active', true))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $recipes = Recipe::query()
            ->where('active', true)
            ->whereIn('recipe_category_id', $categories->modelKeys())
            ->select(['id', 'recipe_category_id', 'published_at', 'created_at'])
            ->with('media')
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->get()
            ->groupBy('recipe_category_id');

        foreach ($categories as $category) {
            $cover = $recipes->get($category->id, collect())
                ->map(fn (Recipe $recipe) => $recipe->getFirstMedia(Recipe::mediaPrefix() . '_cover'))
                ->filter()
                ->first();

            $category->setAttribute(
                'cover_url',
                $cover ? ($cover->getAvailableUrl(['square_card', 'card']) ?: $cover->getUrl()) : null
            );
        }

        return $categories;
    }

    private function indexUrl(string $type, ?string $category): string
    {
        if ($type === 'recipes' && $category) {
            return route('recipes.category', ['category' => $category]);
        }

        return $category
            ? route("{$type}.index", ['category' => $category])
            : route("{$type}.index");
    }
}
