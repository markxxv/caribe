<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageEditorialSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_published_recipes_and_news_using_craft_urls(): void
    {
        $recipe = Recipe::create(['title' => 'Аргентинское блюдо', 'slug' => 'argentine-food', 'active' => true]);
        $news = News::create(['title' => 'Новости Буэнос-Айреса', 'slug' => 'buenos-aires-update', 'active' => true]);
        Recipe::create(['title' => 'Черновик рецепта', 'slug' => 'draft-food', 'active' => false]);
        News::create(['title' => 'Черновик новости', 'slug' => 'draft-update', 'active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Кухня Латинской Америки')
            ->assertSee('Новости Латинской Америки')
            ->assertSee($recipe->url)
            ->assertSee($news->url)
            ->assertDontSee('Черновик рецепта')
            ->assertDontSee('Черновик новости');
    }

    public function test_header_links_use_existing_craft_sections(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('href="' . route('recipes.index') . '"', false)
            ->assertSee('href="' . route('article.index', ['category' => 'emigratsiya']) . '"', false)
            ->assertSee('href="' . route('realestate.index') . '"', false)
            ->assertSee('href="' . route('news.index') . '"', false);
    }
}
