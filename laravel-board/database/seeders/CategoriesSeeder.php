<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    /**
     * Seed the application's categories.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Электроника', 'slug' => 'electronics'],
            ['name' => 'Одежда', 'slug' => 'clothing'],
            ['name' => 'Автомобили', 'slug' => 'cars'],
            ['name' => 'Недвижимость', 'slug' => 'real-estate'],
            ['name' => 'Мебель', 'slug' => 'furniture'],
            ['name' => 'Книги', 'slug' => 'books'],
            ['name' => 'Спорт', 'slug' => 'sport'],
            ['name' => 'Услуги', 'slug' => 'services'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                ['name' => $category['name']]
            );
        }
    }
}
