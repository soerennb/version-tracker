<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\ManageApiTokens;
use App\Models\AuditLog;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\User;
use App\Services\ApiTokenService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ApiTokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tokens_are_hashed_and_cannot_escalate_permissions(): void
    {
        $user = User::factory()->create(['role' => UserRole::EDITOR]);
        $service = app(ApiTokenService::class);
        $token = $service->create($user, ['name' => 'Integration', 'access' => ['mcp', 'rest'], 'abilities' => ['view_versions'], 'expires_at' => now()->addDays(90)->toDateTimeString()]);
        $this->assertNotSame($token->plainTextToken, $token->accessToken->token);
        $this->assertContains('access_rest', $token->accessToken->abilities);
        $this->assertStringNotContainsString($token->plainTextToken, AuditLog::query()->latest('id')->first()->toJson());
        $this->expectException(ValidationException::class);
        $service->create($user, ['name' => 'Escalate', 'access' => ['mcp'], 'abilities' => ['delete_versions'], 'expires_at' => now()->addDay()->toDateTimeString()]);
    }

    public function test_read_and_edit_presets_exclude_destructive_governance_permissions(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $service = app(ApiTokenService::class);
        foreach (['read', 'edit'] as $preset) {
            foreach ($service->preset($admin, $preset) as $ability) {
                $this->assertFalse(str_starts_with($ability, 'delete_'));
                $this->assertNotContains($ability, ['approve_versions', 'publish_versions', 'override_release_readiness', 'manage_dependencies']);
            }
        }
        $this->assertContains('view_dependencies', $service->preset($admin, 'read'));
    }

    public function test_mcp_only_token_cannot_use_rest_and_scoped_admin_cannot_delete(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $software = Software::factory()->create();
        $mcp = $admin->createToken('MCP', ['access_mcp', 'view_software'])->plainTextToken;
        auth()->forgetGuards();
        $this->withToken($mcp)->getJson('/api/softwares')->assertForbidden();
        $rest = $admin->createToken('REST', ['access_rest', 'view_software'])->plainTextToken;
        auth()->forgetGuards();
        $this->withToken($rest)->getJson('/api/softwares')->assertOk();
        auth()->forgetGuards();
        $this->withToken($rest)->deleteJson('/api/softwares/'.$software->id)->assertForbidden();
        auth()->forgetGuards();
        $this->withToken($rest)->postJson('/mcp/versiontracker', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertForbidden();
    }

    public function test_expired_and_revoked_tokens_are_rejected(): void
    {
        $user = User::factory()->create(['role' => UserRole::ADMIN]);
        $expired = $user->createToken('Expired', ['access_rest', 'view_software'], now()->subMinute());
        auth()->forgetGuards();
        $this->withToken($expired->plainTextToken)->getJson('/api/softwares')->assertUnauthorized();
        $revoked = $user->createToken('Revoked', ['access_rest', 'view_software']);
        app(ApiTokenService::class)->revoke($user, $revoked->accessToken);
        auth()->forgetGuards();
        $this->withToken($revoked->plainTextToken)->getJson('/api/softwares')->assertUnauthorized();
    }

    public function test_filament_scopes_tables_and_rechecks_forged_admin_overview(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $editor = User::factory()->create(['role' => UserRole::EDITOR]);
        $other = User::factory()->create(['role' => UserRole::EDITOR]);
        $own = $editor->createToken('Own', ['access_mcp', 'view_versions'])->accessToken;
        $foreign = $other->createToken('Foreign', ['access_mcp', 'view_versions'])->accessToken;
        $this->actingAs($editor);
        Livewire::test(ManageApiTokens::class)->assertCanSeeTableRecords([$own])->assertCanNotSeeTableRecords([$foreign])->set('showAll', true)->assertCanNotSeeTableRecords([$foreign]);
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);
        $this->actingAs($admin);
        Livewire::test(ManageApiTokens::class)->callAction('toggleOverview')->assertCanSeeTableRecords([$own, $foreign])->callTableAction('revoke', $foreign);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $foreign->id]);
    }

    public function test_filament_creation_shows_secret_once_and_viewers_cannot_access(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $editor = User::factory()->create(['role' => UserRole::EDITOR]);
        $this->actingAs($editor);
        $page = Livewire::test(ManageApiTokens::class)->callAction('createToken', data: ['name' => 'UI integration', 'access' => ['mcp'], 'abilities' => ['view_versions'], 'expires_at' => now()->addDays(90)->toDateTimeString()])->assertHasNoActionErrors();
        $this->assertDatabaseHas('personal_access_tokens', ['name' => 'UI integration', 'tokenable_id' => $editor->id]);
        $mounted = $page->get('mountedActions');
        $this->assertSame('showToken', $mounted[0]['name']);
        $secret = $mounted[0]['arguments']['token'];
        $page->assertSee($secret)->call('unmountAction')->assertDontSee($secret);
        $viewer = User::factory()->create(['role' => UserRole::VIEWER]);
        $this->actingAs($viewer);
        $this->assertFalse(ManageApiTokens::canAccess());
    }

    public function test_editor_cannot_revoke_another_users_token(): void
    {
        $editor = User::factory()->create(['role' => UserRole::EDITOR]);
        $other = User::factory()->create();
        $token = $other->createToken('Other', ['access_mcp', 'view_versions'])->accessToken;
        $this->expectException(AuthorizationException::class);
        app(ApiTokenService::class)->revoke($editor, $token);
    }

    public function test_read_only_dependency_token_cannot_delete_in_rest(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('Read', ['access_rest', 'view_dependencies'])->plainTextToken;
        $dependency = SoftwareDependency::factory()->create();
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/software-dependencies')->assertOk();
        auth()->forgetGuards();
        $this->withToken($token)->deleteJson('/api/software-dependencies/'.$dependency->id)->assertForbidden();
    }

    public function test_mcp_requires_bearer_token_even_with_browser_session(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->postJson('/mcp/versiontracker', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();
    }
}
