<?php

namespace Database\Seeders;

use App\Models\RecipeCategory;
use Illuminate\Database\Seeder;

class RecipeCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'zavtraki',              'title' => 'Завтраки'],
            ['slug' => 'supy',                  'title' => 'Супы'],
            ['slug' => 'salaty',                'title' => 'Салаты'],
            ['slug' => 'zakuski-i-sousy',       'title' => 'Закуски и соусы'],
            ['slug' => 'kurica-i-indejka',      'title' => 'Курица и индейка'],
            ['slug' => 'myasnye-blyuda',        'title' => 'Мясные блюда'],
            ['slug' => 'ryba-i-moreprodukty',   'title' => 'Рыба и морепродукты'],
            ['slug' => 'ovoshchnye-blyuda-i-garniry', 'title' => 'Овощные блюда и гарниры'],
            ['slug' => 'torty-i-chizkejki',     'title' => 'Торты и чизкейки'],
            ['slug' => 'pechene-keksy-i-deserty', 'title' => 'Печенье, кексы и другие десерты'],
            ['slug' => 'napitki-i-kokteyli',    'title' => 'Напитки и коктейли'],
        ];

        foreach ($categories as $i => $category) {
            RecipeCategory::updateOrCreate(
                ['slug' => $category['slug']],
                ['title' => $category['title'], 'active' => true, 'sort_order' => $i + 1],
            );
        }
    }
}
