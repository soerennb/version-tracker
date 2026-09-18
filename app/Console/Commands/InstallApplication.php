<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Settings\InstallationState;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\DemoUserSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

#[Signature('app:install
    {--demo : Seed the demo user and data instead of creating an administrator}
    {--no-demo : Do not ask whether to seed demo data}
    {--reset-demo : Reset a database previously initialized with the demo profile}
    {--force : Confirm the destructive demo reset}
    {--admin-name= : First administrator name}
    {--admin-email= : First administrator email}
    {--admin-password-stdin : Read the first administrator password from standard input}')]
#[Description('Initialize the application database and its first administrator')]
class InstallApplication extends Command
{
    public function handle(): int
    {
        if ($this->call('migrate', ['--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->option('reset-demo')) {
            $state = app(InstallationState::class);

            if ($state->profile !== 'demo') {
                $this->components->error('Demo reset is allowed only for an application marked with the demo profile.');

                return self::FAILURE;
            }

            if (! $this->option('force')) {
                $this->components->error('Demo reset is destructive. Re-run with --force to confirm.');

                return self::FAILURE;
            }

            if ($this->call('migrate:fresh', ['--force' => true]) !== self::SUCCESS) {
                return self::FAILURE;
            }
            app()->forgetInstance(InstallationState::class);
            Storage::disk(config('filesystems.default', 'local'))->deleteDirectory('attachments');
        }

        if (User::query()->exists()) {
            $this->components->error('Installation stopped because one or more users already exist.');

            return self::FAILURE;
        }

        $shouldCreateDemoData = $this->option('demo') || $this->option('reset-demo') || (! $this->option('no-demo') && $this->confirm('Create demo data?', false));

        if ($shouldCreateDemoData) {
            $password = bin2hex(random_bytes(18));
            app(DemoUserSeeder::class)->run($password);
            app(DemoDataSeeder::class)->run();
            app(InstallationState::class)->complete('demo');

            $this->components->info('Demo data and the demo administrator were created.');
            $this->components->warn('Demo credentials (store them securely):');
            $this->line('  Email: demo@example.com');
            $this->line("  Password: {$password}");
        } else {
            $this->createAdministrator();
            app(InstallationState::class)->complete();
        }

        if (! app()->runningUnitTests()) {
            $this->call('storage:link', ['--force' => true]);
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
        $name = $this->option('admin-name') ?: $this->ask('Administrator name');
        $email = $this->option('admin-email') ?: $this->ask('Administrator email');

        while (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->components->error('Enter a valid email address.');
            $email = $this->ask('Administrator email');
        }

        $password = $this->option('admin-password-stdin')
            ? trim((string) stream_get_contents(STDIN))
            : $this->secret('Administrator password (at least 12 characters)');

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
