<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->expectsConfirmation('Demo data creates a public administrator password. Continue?', 'yes')
            ->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'email' => 'demo@example.com',
            'role' => UserRole::ADMIN->value,
        ]);
        $this->assertDatabaseHas('software', ['name' => 'Aurora Suite']);
    }

    public function test_it_stops_when_demo_data_is_not_explicitly_confirmed(): void
    {
        $this->artisan('app:install --demo')
            ->expectsConfirmation('Demo data creates a public administrator password. Continue?', 'no')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_refuses_to_initialize_an_application_with_existing_users(): void
    {
        User::factory()->create();

        $this->artisan('app:install')
            ->expectsOutputToContain('Installation stopped because one or more users already exist.')
            ->assertFailed();
    }
}
