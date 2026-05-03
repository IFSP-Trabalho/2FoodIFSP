<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departmentId = DB::table('departments')->value('id');

        if ($departmentId === null) {
            $departmentId = Str::uuid()->toString();

            DB::table('departments')->insert([
                'id' => $departmentId,
                'name' => 'Factory Department',
                'slug' => 'factory-department',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return [
            'id' => 'firebase_' . Str::lower(Str::random(24)),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement(['admin', 'kitchen', 'finance', 'waiter', 'whatsapp_agent']),
            'department_id' => $departmentId,
            'must_reset_password' => true,
        ];
    }
}
