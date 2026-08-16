<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

#[Signature('app:install {--demo : Seed the demo user and data instead of creating an administrator}')]
#[Description('Initialize the application database and its first administrator')]
class InstallApplication extends Command
{
    public function handle(): int
    {
        $this->call('migrate', ['--force' => true]);

        if (User::query()->exists()) {
            $this->components->error('Installation stopped because one or more users already exist.');

            return self::FAILURE;
        }

        if ($this->option('demo') || $this->confirm('Create demo data?', false)) {
            $this->call('db:seed');
            $this->components->info('Demo data and the demo administrator were created.');
        } else {
            $this->createAdministrator();
        }

        if (! app()->runningUnitTests()) {
            $this->call('storage:link');
            Artisan::call('optimize:clear');
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');
        }

        $this->components->info('VersionTracker is ready.');

        return self::SUCCESS;
    }

    private function createAdministrator(): void
    {
        $name = $this->ask('Administrator name');
        $email = $this->ask('Administrator email');

        while (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('Enter a valid email address.');
            $email = $this->ask('Administrator email');
        }

        $password = $this->secret('Administrator password (at least 12 characters)');

        while (mb_strlen((string) $password) < 12) {
            $this->components->error('The password must be at least 12 characters long.');
            $password = $this->secret('Administrator password (at least 12 characters)');
        }

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make((string) $password),
            'role' => UserRole::ADMIN,
            'email_verified_at' => now(),
        ]);

        $this->components->info('Administrator created.');
    }
}
