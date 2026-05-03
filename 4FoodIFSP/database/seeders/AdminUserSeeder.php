<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminUid = env('ADMIN_FIREBASE_UID');

        if (! is_string($adminUid) || trim($adminUid) === '') {
            $this->command?->warn('ADMIN_FIREBASE_UID não definido. Seeder de admin foi ignorado.');

            return;
        }

        $adminDepartmentId = DB::table('departments')
            ->where('slug', 'admin')
            ->value('id');

        if ($adminDepartmentId === null) {
            $this->command?->warn('Departamento admin não encontrado. Seeder de admin foi ignorado.');

            return;
        }

        DB::table('users')->updateOrInsert(
            ['id' => $adminUid],
            [
                'name' => 'Administrador',
                'email' => 'admin@restaurante.com',
                'role' => 'admin',
                'department_id' => $adminDepartmentId,
                'must_reset_password' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
