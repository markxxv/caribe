<?php

namespace Tests\Feature;

use App\Filament\Resources\BlogCategories\BlogCategoryResource;
use App\Filament\Resources\Blogs\BlogResource;
use App\Filament\Resources\News\NewsResource;
use App\Filament\Resources\PersonalBlogs\PersonalBlogResource;
use App\Filament\Resources\Realestate\Cities\CityResource;
use App\Filament\Resources\Realestate\RealestateResource;
use App\Filament\Resources\RecipeCategories\RecipeCategoryResource;
use App\Filament\Resources\Recipes\RecipeResource;
use App\Filament\Resources\Tours\TourResource;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\News;
use App\Models\PersonalBlog;
use App\Models\Realestate\City;
use App\Models\Realestate\Country;
use App\Models\Realestate\Realestate;
use App\Models\Realestate\Region;
use App\Models\Recipe;
use App\Models\RecipeCategory;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendEditLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_editable_public_pages_link_to_their_records_only_for_authenticated_admins(): void
    {
        $this->withoutVite();

        $tour = Tour::create(['title' => 'Tour', 'active' => true]);
        $blogCategory = BlogCategory::create(['title' => 'Blog category', 'active' => true]);
        $blog = Blog::create(['title' => 'Blog', 'active' => true, 'blog_category_id' => $blogCategory->id]);
        $news = News::create(['title' => 'News', 'active' => true]);
        $article = PersonalBlog::create(['title' => 'Article', 'active' => true]);
        $recipeCategory = RecipeCategory::create(['title' => 'Recipe category', 'active' => true]);
        $recipe = Recipe::create(['title' => 'Recipe', 'active' => true, 'recipe_category_id' => $recipeCategory->id]);
        $property = Realestate::create(['title' => 'Property', 'active' => true]);
        $country = Country::create(['name' => 'Argentina']);
        $region = Region::create(['name' => 'Buenos Aires', 'country_id' => $country->id]);
        $city = City::create(['name' => 'Pinamar', 'region_id' => $region->id]);

        $pages = [
            [route('tours.show', ['slug' => $tour->slug]), TourResource::class, $tour],
            [route('blog.show', ['slug' => $blog->slug]), BlogResource::class, $blog],
            [route('news.show', ['slug' => $news->slug]), NewsResource::class, $news],
            [route('article.show', ['slug' => $article->slug]), PersonalBlogResource::class, $article],
            [route('recipes.show', ['slug' => $recipe->slug]), RecipeResource::class, $recipe],
            [route('blog.category', ['category' => $blogCategory->slug]), BlogCategoryResource::class, $blogCategory],
            [route('recipes.category', ['category' => $recipeCategory->slug]), RecipeCategoryResource::class, $recipeCategory],
            [route('realestate.show', ['url' => $property->slug]), RealestateResource::class, $property],
            [$city->publicUrl(), CityResource::class, $city],
        ];

        foreach ($pages as [$url]) {
            $this->get($url)->assertOk()->assertDontSee('data-admin-edit-link', false);
        }

        $this->actingAs(User::factory()->create());

        foreach ($pages as [$url, $resource, $record]) {
            $response = $this->get($url)
                ->assertOk()
                ->assertSee('data-admin-edit-link', false)
                ->assertSee('href="' . $resource::getUrl('edit', ['record' => $record], panel: 'dashboard') . '"', false);

            if (in_array($resource, [RealestateResource::class, CityResource::class], true)) {
                $this->assertTrue($response->headers->hasCacheControlDirective('private'));
                $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
                $response->assertHeader('Vary', 'Cookie');
            }
        }

        $this->get(route('home'))->assertOk()->assertDontSee('data-admin-edit-link', false);
    }
}
