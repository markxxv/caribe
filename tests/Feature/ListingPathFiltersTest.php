<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Country;
use App\Models\News;
use App\Models\PersonalBlog;
use App\Models\Recipe;
use App\Models\Tour;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingPathFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_country_slugs_filter_tours_blog_news_and_emigration_articles(): void
    {
        $argentina = Country::create(['title' => 'Аргентина', 'slug' => 'argentina']);
        $chile = Country::create(['title' => 'Чили', 'slug' => 'chile']);

        $blogAr = Blog::create(['title' => 'Блог об Аргентине', 'active' => true]);
        $blogCl = Blog::create(['title' => 'Блог о Чили', 'active' => true]);
        $blogAr->countries()->attach($argentina);
        $blogCl->countries()->attach($chile);

        $tourAr = Tour::create(['title' => 'Тур по Аргентине', 'active' => true]);
        $tourCl = Tour::create(['title' => 'Тур по Чили', 'active' => true]);
        $tourAr->countries()->attach($argentina);
        $tourCl->countries()->attach($chile);

        $newsAr = News::create(['title' => 'Новости Аргентины', 'active' => true]);
        $newsCl = News::create(['title' => 'Новости Чили', 'active' => true]);
        $newsAr->countries()->attach($argentina);
        $newsCl->countries()->attach($chile);

        $articleAr = PersonalBlog::create([
            'title' => 'Жизнь в Аргентине',
            'category_slug' => 'emigratsiya',
            'category_name' => 'Эмиграция',
            'active' => true,
        ]);
        $articleCl = PersonalBlog::create([
            'title' => 'Жизнь в Чили',
            'category_slug' => 'emigratsiya',
            'category_name' => 'Эмиграция',
            'active' => true,
        ]);
        $articleAr->countries()->attach($argentina);
        $articleCl->countries()->attach($chile);

        foreach ([
            ['/blog/country/argentina', 'Блог об Аргентине', 'Блог о Чили'],
            ['/tours/argentina', 'Тур по Аргентине', 'Тур по Чили'],
            ['/news/country/argentina', 'Новости Аргентины', 'Новости Чили'],
            ['/articles/emigratsiya/country/argentina', 'Жизнь в Аргентине', 'Жизнь в Чили'],
        ] as [$url, $visible, $hidden]) {
            $this->get($url)->assertOk()->assertSee($visible)->assertDontSee($hidden);
        }
    }

    public function test_legacy_country_search_and_page_parameters_redirect_to_slugs(): void
    {
        $argentina = Country::create(['title' => 'Аргентина', 'slug' => 'argentina']);
        PersonalBlog::create([
            'title' => 'Эмиграция в Аргентину',
            'category_slug' => 'emigratsiya',
            'category_name' => 'Эмиграция',
            'active' => true,
        ]);

        $this->get('/blog?country=' . $argentina->id . '&search=Buenos&page=2')
            ->assertStatus(301)
            ->assertRedirect('/blog/country/argentina/search/Buenos/page/2');

        $this->get('/news?country=' . $argentina->id)
            ->assertStatus(301)
            ->assertRedirect('/news/country/argentina');

        $this->get('/articles/emigratsiya?country=' . $argentina->id)
            ->assertStatus(301)
            ->assertRedirect('/articles/emigratsiya/country/argentina');

        $this->get('/tours?country=' . $argentina->id)
            ->assertStatus(301)
            ->assertRedirect('/tours/argentina');

        $this->get('/recipes?search=Empanada')
            ->assertStatus(301)
            ->assertRedirect('/recipes/search/Empanada');
    }

    public function test_combined_path_filters_preserve_search_and_reset_page_on_country_change(): void
    {
        Country::create(['title' => 'Аргентина', 'slug' => 'argentina']);
        $chile = Country::create(['title' => 'Чили', 'slug' => 'chile']);

        $news = News::create(['title' => 'Экономика Чили', 'active' => true]);
        $news->countries()->attach($chile);

        $this->get('/news/country/chile/search/Экономика')
            ->assertOk()
            ->assertSee('Экономика Чили')
            ->assertSee('value="chile" selected', false)
            ->assertSee('/news/search/' . rawurlencode('Экономика'), false);

        $this->get('/news/country/chile/search/Ничего/page/2')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, follow"', false);

        $this->get('/news/page/1')
            ->assertStatus(301)
            ->assertRedirect('/news');
    }

    public function test_pagination_uses_path_page_segments(): void
    {
        foreach (range(1, 14) as $index) {
            Blog::create(['title' => 'Публикация ' . $index, 'active' => true]);
        }

        $this->get('/blog')
            ->assertOk()
            ->assertSee('/blog/page/2', false)
            ->assertDontSee('?page=2', false);

        $this->get('/blog/page/2')
            ->assertOk()
            ->assertSee('name="robots" content="noindex, follow"', false);

        $this->get('/blog?page=2')
            ->assertStatus(301)
            ->assertRedirect('/blog/page/2');
    }

    public function test_recipe_search_uses_clean_path_and_other_sections_remain_accessible(): void
    {
        Recipe::create(['title' => 'Эмпанада с мясом', 'active' => true]);
        Recipe::create(['title' => 'Кофе', 'active' => true]);

        $this->get('/recipes/search/' . rawurlencode('Эмпанада'))
            ->assertOk()
            ->assertSee('Эмпанада с мясом')
            ->assertDontSee('Кофе');

        $this->get('/recipes/country/argentina')->assertNotFound();
        $this->get('/blog/country/not-a-country')->assertNotFound();
        $this->get('/blog/country/argentina/country/chile')->assertNotFound();
    }
}
