<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_receives_only_read_permissions(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('view_software'));
        $this->assertTrue(Gate::forUser($user)->allows('view_vulnerabilities'));
        $this->assertTrue(Gate::forUser($user)->allows('download_files'));
        $this->assertFalse(Gate::forUser($user)->allows('create_software'));
        $this->assertFalse(Gate::forUser($user)->allows('delete_versions'));
    }

    public function test_editor_receives_mutation_permissions_without_destructive_access(): void
    {
        $user = User::factory()->editor()->create();

        $this->assertTrue(Gate::forUser($user)->allows('view_versions'));
        $this->assertTrue(Gate::forUser($user)->allows('create_software'));
        $this->assertTrue(Gate::forUser($user)->allows('approve_versions'));
        $this->assertFalse(Gate::forUser($user)->allows('delete_software'));
        $this->assertFalse(Gate::forUser($user)->allows('delete_vulnerabilities'));
    }

    public function test_admin_has_full_permissions(): void
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($user)->allows('delete_software'));
        $this->assertTrue(Gate::forUser($user)->allows('delete_vulnerabilities'));
    }

    public function test_custom_ability_overrides_role_defaults(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['create_software'],
        ]);

        $this->assertTrue(Gate::forUser($user)->allows('create_software'));
    }
}
