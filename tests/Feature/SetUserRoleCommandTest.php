<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SetUserRoleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_role_by_email(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);

        $this->artisan('user:set-role', [
            'user' => $user->email,
            'role' => 'editor',
        ])->assertSuccessful();

        $user->refresh();

        $this->assertSame(UserRole::EDITOR, $user->role);
        $this->assertNull($user->abilities);
    }

    public function test_it_updates_custom_abilities_when_provided(): void
    {
        $user = User::factory()->create();

        $this->artisan('user:set-role', [
            'user' => (string) $user->id,
            'role' => 'viewer',
            '--abilities' => 'create_software,view_versions',
        ])->assertSuccessful();

        $user->refresh();

        $this->assertSame(UserRole::VIEWER, $user->role);
        $this->assertSame(['create_software', 'view_versions'], $user->abilities);
    }

    public function test_it_fails_for_unknown_user(): void
    {
        $this->artisan('user:set-role', [
            'user' => 'unknown@example.com',
            'role' => 'admin',
        ])->assertFailed();
    }

    public function test_it_fails_for_invalid_role(): void
    {
        $user = User::factory()->create();

        $this->artisan('user:set-role', [
            'user' => $user->email,
            'role' => 'superadmin',
        ])->assertFailed();
    }

    public function test_it_fails_for_invalid_custom_ability(): void
    {
        $user = User::factory()->create();

        $this->artisan('user:set-role', [
            'user' => $user->email,
            'role' => 'editor',
            '--abilities' => 'not_existing_ability',
        ])->assertFailed();
    }
}
