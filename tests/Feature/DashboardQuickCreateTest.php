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
use App\Filament\Widgets\QuickCreateWidget;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardQuickCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_offers_creation_of_main_material_types_only(): void
    {
        $this->actingAs(User::factory()->create());

        $panel = Filament::getPanel('dashboard');
        Filament::setCurrentPanel($panel);

        $this->assertContains(QuickCreateWidget::class, $panel->getWidgets());

        $widget = Livewire::test(QuickCreateWidget::class)
            ->assertSee('Создать материал');

        foreach ([
            TourResource::class,
            BlogResource::class,
            NewsResource::class,
            PersonalBlogResource::class,
            RecipeResource::class,
            RealestateResource::class,
        ] as $resource) {
            $widget->assertSeeHtml('href="' . $resource::getUrl('create', panel: 'dashboard') . '"');
        }

        foreach ([
            BlogCategoryResource::class,
            RecipeCategoryResource::class,
            CityResource::class,
        ] as $resource) {
            $widget->assertDontSeeHtml('href="' . $resource::getUrl('create', panel: 'dashboard') . '"');
        }
    }
}
