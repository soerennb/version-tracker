<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Prevent accidental demo data and credentials from generic db:seed calls.
     */
    public function run(): void
    {
        throw new RuntimeException('Use php artisan app:install --demo for the explicit demo profile.');
    }
}
