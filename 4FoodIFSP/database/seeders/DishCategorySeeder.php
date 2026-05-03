<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DishCategorySeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Pratos Principais', 'slug' => 'main_course'],
            ['name' => 'Bebidas', 'slug' => 'drinks'],
            ['name' => 'Sobremesas', 'slug' => 'desserts'],
        ];

        foreach ($categories as $category) {
            $existing = DB::table('dish_categories')
                ->where('slug', $category['slug'])
                ->first();

            if ($existing === null) {
                DB::table('dish_categories')->insert([
                    'id' => Str::uuid()->toString(),
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('dish_categories')
                ->where('id', $existing->id)
                ->update([
                    'name' => $category['name'],
                    'updated_at' => now(),
                ]);
        }
    }
}
