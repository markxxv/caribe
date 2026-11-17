<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Blogs\BlogResource;
use App\Filament\Resources\Events\EventResource;
use App\Filament\Resources\News\NewsResource;
use App\Filament\Resources\PersonalBlogs\PersonalBlogResource;
use App\Filament\Resources\Realestate\RealestateResource;
use App\Filament\Resources\Recipes\RecipeResource;
use App\Filament\Resources\Tours\TourResource;
use Filament\Widgets\Widget;

class QuickCreateWidget extends Widget
{
    protected static ?int $sort = -10;

    protected static bool $isLazy = false;

    protected int | string | array $columnSpan = 'full';

    protected string $view = 'filament.widgets.quick-create';

    protected function getViewData(): array
    {
        $materials = [
            [
                'resource' => TourResource::class,
                'title' => 'Новый тур',
                'description' => 'Экскурсии и путешествия',
                'icon' => 'tabler-plane',
                'tone' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300',
            ],
            [
                'resource' => BlogResource::class,
                'title' => 'Статья в блог',
                'description' => 'Истории и путеводители',
                'icon' => 'tabler-book',
                'tone' => 'bg-violet-50 text-violet-700 dark:bg-violet-950/50 dark:text-violet-300',
            ],
            [
                'resource' => NewsResource::class,
                'title' => 'Новая новость',
                'description' => 'События и актуальные новости',
                'icon' => 'tabler-news',
                'tone' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
            ],
            [
                'resource' => EventResource::class,
                'title' => 'Подборка мероприятий',
                'description' => 'Афиша интересных событий',
                'icon' => 'tabler-calendar-event',
                'tone' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300',
            ],
            [
                'resource' => PersonalBlogResource::class,
                'title' => 'Статья об эмиграции',
                'description' => 'Переезд и жизнь за рубежом',
                'icon' => 'tabler-user-edit',
                'tone' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300',
            ],
            [
                'resource' => RecipeResource::class,
                'title' => 'Новый рецепт',
                'description' => 'Кухня Латинской Америки',
                'icon' => 'tabler-chef-hat',
                'tone' => 'bg-orange-50 text-orange-700 dark:bg-orange-950/50 dark:text-orange-300',
            ],
            [
                'resource' => RealestateResource::class,
                'title' => 'Объект недвижимости',
                'description' => 'Продажа и аренда',
                'icon' => 'tabler-building-community',
                'tone' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
            ],
        ];

        $items = [];

        foreach ($materials as $material) {
            $resource = $material['resource'];

            if (! $resource::canCreate()) {
                continue;
            }

            $material['url'] = $resource::getUrl('create', panel: 'dashboard');
            $items[] = $material;
        }

        return ['items' => $items];
    }
}
