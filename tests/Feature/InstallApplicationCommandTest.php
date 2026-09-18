<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Settings\InstallationState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class InstallApplicationCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_administrator_for_an_empty_application(): void
    {
        $this->artisan('app:install')
            ->expectsConfirmation('Create demo data?', 'no')
            ->expectsQuestion('Administrator name', 'Administrator')
            ->expectsQuestion('Administrator email', 'admin@example.com')
            ->expectsQuestion('Administrator password (at least 12 characters)', 'secure-password')
            ->assertSuccessful();

        $administrator = User::query()->sole();

        $this->assertSame('Administrator', $administrator->name);
        $this->assertSame('admin@example.com', $administrator->email);
        $this->assertSame(UserRole::ADMIN, $administrator->role);
    }

    public function test_it_accepts_administrator_identity_options_for_unattended_installation(): void
    {
        $this->artisan('app:install --no-demo --admin-name="Automated Administrator" --admin-email=automated@example.com')
            ->expectsQuestion('Administrator password (at least 12 characters)', 'secure-password')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'name' => 'Automated Administrator',
            'email' => 'automated@example.com',
            'role' => UserRole::ADMIN->value,
        ]);
    }

    public function test_it_seeds_demo_data_when_requested(): void
    {
        $this->artisan('app:install --demo')
            ->expectsOutputToContain('Email: demo@example.com')
            ->expectsOutputToContain('Password:')
            ->assertSuccessful();

        $demoUser = User::query()->where('email', 'demo@example.com')->sole();

        $this->assertSame(UserRole::ADMIN, $demoUser->role);
        $this->assertFalse(Hash::check('password', $demoUser->password));
        $this->assertDatabaseHas('software', ['name' => 'Aurora Suite']);
        $this->assertSame('demo', app(InstallationState::class)->profile);
    }

    public function test_generic_database_seeding_cannot_create_demo_credentials(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Use php artisan app:install --demo');

        $this->artisan('db:seed');
    }

    public function test_demo_reset_requires_an_explicit_force_flag(): void
    {
        $this->artisan('app:install --demo')->assertSuccessful();

        $this->artisan('app:install --reset-demo')
            ->expectsOutputToContain('Demo reset is destructive')
            ->assertFailed();

        $this->assertDatabaseCount('users', 1);
        $this->assertSame('demo', app(InstallationState::class)->profile);
    }

    public function test_it_refuses_to_initialize_an_application_with_existing_users(): void
    {
        User::factory()->create();

        $this->artisan('app:install')
            ->expectsOutputToContain('Installation stopped because one or more users already exist.')
            ->assertFailed();
    }
}
