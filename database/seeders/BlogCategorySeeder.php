<?php

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'dostoprimechatelnosti', 'title' => 'Достопримечательности'],
            ['slug' => 'priroda-i-nacionalnye-parki', 'title' => 'Природа и национальные парки'],
            ['slug' => 'ostrova-i-plyazhi', 'title' => 'Острова и пляжи'],
            ['slug' => 'zhivotnyj-i-rastitelnyj-mir', 'title' => 'Животный и растительный мир'],
            ['slug' => 'istoriya', 'title' => 'История'],
            ['slug' => 'izvestnye-lichnosti', 'title' => 'Известные личности'],
            ['slug' => 'indejcy-i-kultura', 'title' => 'Индейцы и культура'],
            ['slug' => 'legendy-i-mistika', 'title' => 'Легенды и мистика'],
            ['slug' => 'eda-i-napitki', 'title' => 'Еда и напитки'],
            ['slug' => 'zhizn-v-strane-i-sovety', 'title' => 'Жизнь в стране и советы'],
        ];

        foreach ($categories as $i => $category) {
            BlogCategory::updateOrCreate(
                ['slug' => $category['slug']],
                ['title' => $category['title'], 'active' => true, 'sort_order' => $i + 1],
            );
        }
    }
}
