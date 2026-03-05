<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@orderly.com'],
            [
                'name' => 'Admin Orderly',
                'email' => 'admin@orderly.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
    }
}