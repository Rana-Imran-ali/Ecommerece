<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Primary Admin account
        User::updateOrCreate(
            ['email' => 'ranaimranali2210@gmail.com'],
            [
                'name'     => 'Admin',
                'password' => 'admin123',
                'role'     => 'admin',
            ]
        );

        // Fallback admin account
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name'     => 'Admin',
                'password' => 'admin123',
                'role'     => 'admin',
            ]
        );

        // Test customer account
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name'     => 'Test User',
                'password' => bcrypt('password123'),
                'role'     => 'customer',
            ]
        );
    }
}