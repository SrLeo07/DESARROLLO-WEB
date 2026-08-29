<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Usuario Demo',
                'password' => 'password123',
            ],
        );

        $this->call(ProductSeeder::class);
    }
}
