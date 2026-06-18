<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'phone' => env('SUPER_ADMIN_PHONE', '6280000000000'),
                'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
                'role' => UserRole::SuperAdmin,
                'status' => UserStatus::Active,
            ]
        );
    }
}
