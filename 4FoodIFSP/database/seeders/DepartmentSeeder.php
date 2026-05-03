<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $departments = [
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'Cozinha', 'slug' => 'kitchen'],
            ['name' => 'Financeiro', 'slug' => 'finance'],
            ['name' => 'Garçom', 'slug' => 'waiter'],
        ];

        foreach ($departments as $department) {
            $existing = DB::table('departments')
                ->where('slug', $department['slug'])
                ->first();

            if ($existing === null) {
                DB::table('departments')->insert([
                    'id' => Str::uuid()->toString(),
                    'name' => $department['name'],
                    'slug' => $department['slug'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('departments')
                ->where('id', $existing->id)
                ->update([
                    'name' => $department['name'],
                    'updated_at' => now(),
                ]);
        }
    }
}
