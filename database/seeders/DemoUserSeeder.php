<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    /**
     * Create the demo user for local development.
     */
    public function run(?string $password = null): User
    {
        return User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make($password ?? bin2hex(random_bytes(18))),
                'role' => UserRole::ADMIN,
                'email_verified_at' => now(),
            ]
        );
    }
}
