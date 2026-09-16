<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\UserRole;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\Software;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\RuntimeSettings;
use App\Settings\GovernanceSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class McpContentTest extends TestCase
{
    use RefreshDatabase;

    protected function callTool(string $token, string $name, array $arguments = []): TestResponse
    {
        auth()->forgetGuards();
        $response = $this->withToken($token)->postJson('/mcp/versiontracker', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => $name, 'arguments' => $arguments]]);

        return $response;
    }

    protected function token(array $abilities = ['*'], ?User $user = null): string
    {
        return ($user ?? User::factory()->create(['role' => UserRole::ADMIN]))->createToken('MCP', $abilities)->plainTextToken;
    }

    public function test_authentication_initialization_and_scoped_tool_discovery(): void
    {
        $this->postJson('/mcp/versiontracker', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();
        $token = $this->token(['access_mcp', 'view_software']);
        auth()->forgetGuards();
        $this->withToken($token)->postJson('/mcp/versiontracker', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => ['protocolVersion' => '2025-06-18', 'capabilities' => new \stdClass, 'clientInfo' => ['name' => 'Test', 'version' => '1']]])->assertOk()->assertJsonPath('result.serverInfo.name', 'VersionTracker');
        auth()->forgetGuards();
        $response = $this->withToken($token)->postJson('/mcp/versiontracker', ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list'])->assertOk();
        $names = array_column($response->json('result.tools'), 'name');
        $this->assertContains('list_software', $names);
        $this->assertNotContains('delete_software', $names);
        $this->callTool($token, 'delete_software', ['id' => 1])->assertJsonStructure(['error']);
    }

    public function test_software_crud_search_pagination_and_audit(): void
    {
        $token = $this->token();
        $created = $this->callTool($token, 'create_software', ['data' => ['name' => 'MCP product', 'description' => 'First', 'status' => 'active', 'compliance_status' => 'unknown']])->assertOk()->assertJsonPath('result.isError', false);
        $id = $created->json('result.structuredContent.data.id');
        $this->assertNotNull($id);
        $this->callTool($token, 'show_software', ['id' => $id])->assertJsonPath('result.structuredContent.data.name', 'MCP product');
        $this->callTool($token, 'update_software', ['id' => $id, 'data' => ['name' => 'Updated product', 'description' => 'Second', 'status' => 'active', 'compliance_status' => 'unknown']])->assertJsonPath('result.structuredContent.data.name', 'Updated product');
        $this->callTool($token, 'list_software', ['search' => 'Updated product', 'per_page' => 1])->assertJsonPath('result.structuredContent.meta.total', 1);
        $this->callTool($token, 'delete_software', ['id' => $id])->assertJsonPath('result.structuredContent.deleted', true);
        $this->assertSoftDeleted('software', ['id' => $id]);
        $this->assertDatabaseHas('audit_logs', ['model_id' => $id, 'action' => 'software.created', 'interface' => 'mcp']);
    }

    public function test_version_crud_enforces_drafts_and_semver(): void
    {
        $token = $this->token();
        $software = Software::factory()->create();
        $response = $this->callTool($token, 'create_versions', ['data' => ['software_id' => $software->id, 'version_number' => '1.2.3', 'release_date' => '2026-09-16', 'status' => 'published', 'approval_status' => 'approved']])->assertJsonPath('result.isError', false);
        $id = $response->json('result.structuredContent.data.id');
        $this->assertDatabaseHas('versions', ['id' => $id, 'status' => 'draft', 'approval_status' => 'pending']);
        $this->callTool($token, 'update_versions', ['id' => $id, 'data' => ['version_number' => '1.2.4', 'release_date' => '2026-09-16']])->assertJsonPath('result.isError', false);
        $this->callTool($token, 'list_versions', ['software_id' => $software->id])->assertJsonPath('result.structuredContent.meta.total', 1);
        $this->callTool($token, 'create_versions', ['data' => ['software_id' => $software->id, 'version_number' => 'invalid', 'release_date' => '2026-09-16']])->assertJsonPath('result.isError', true);
        $this->callTool($token, 'delete_versions', ['id' => $id])->assertJsonPath('result.structuredContent.deleted', true);
    }

    public function test_text_crud_rejects_duplicate_languages(): void
    {
        $token = $this->token();
        $version = Version::factory()->create();
        $data = ['title' => 'Notes', 'content' => 'Release notes', 'language' => 'de'];
        $id = $this->callTool($token, 'create_text_contents', ['version_id' => $version->id, 'data' => $data])->assertJsonPath('result.isError', false)->json('result.structuredContent.data.id');
        $this->callTool($token, 'create_text_contents', ['version_id' => $version->id, 'data' => $data])->assertJsonPath('result.isError', true);
        $this->callTool($token, 'update_text_contents', ['id' => $id, 'data' => ['title' => 'Updated']])->assertJsonPath('result.structuredContent.data.title', 'Updated');
        $this->callTool($token, 'list_text_contents', ['version_id' => $version->id])->assertJsonPath('result.structuredContent.meta.total', 1);
        $this->callTool($token, 'delete_text_contents', ['id' => $id])->assertJsonPath('result.structuredContent.deleted', true);
    }

    public function test_dependency_crud_prevents_cycles_and_duplicates(): void
    {
        $token = $this->token();
        [$one,$two] = Software::factory()->count(2)->create()->all();
        $data = ['software_id' => $one->id, 'depends_on_software_id' => $two->id, 'dependency_type' => 'runtime'];
        $id = $this->callTool($token, 'create_software_dependencies', ['data' => $data])->assertJsonPath('result.isError', false)->json('result.structuredContent.data.id');
        $this->callTool($token, 'create_software_dependencies', ['data' => $data])->assertJsonPath('result.isError', true);
        $this->callTool($token, 'create_software_dependencies', ['data' => ['software_id' => $two->id, 'depends_on_software_id' => $one->id, 'dependency_type' => 'runtime']])->assertJsonPath('result.isError', true);
        $this->callTool($token, 'update_software_dependencies', ['id' => $id, 'data' => ['dependency_type' => 'build']])->assertJsonPath('result.isError', false);
        $this->callTool($token, 'show_software_dependencies', ['id' => $id])->assertJsonPath('result.isError', false);
        $this->callTool($token, 'delete_software_dependencies', ['id' => $id])->assertJsonPath('result.structuredContent.deleted', true);
    }

    public function test_vulnerability_crud_validates_cvss_and_version_relationship(): void
    {
        $token = $this->token();
        $version = Version::factory()->create();
        $data = ['cve_id' => 'CVE-2026-12345', 'affected_version_id' => $version->id, 'severity' => 'high', 'description' => 'Test advisory', 'published_date' => '2026-09-16'];
        $id = $this->callTool($token, 'create_vulnerabilities', ['data' => $data])->assertJsonPath('result.isError', false)->json('result.structuredContent.data.id');
        $this->callTool($token, 'update_vulnerabilities', ['id' => $id, 'data' => ['cvss_score' => 11]])->assertJsonPath('result.isError', true);
        $this->callTool($token, 'update_vulnerabilities', ['id' => $id, 'data' => ['description' => 'Updated advisory']])->assertJsonPath('result.structuredContent.data.description', 'Updated advisory');
        $this->callTool($token, 'list_vulnerabilities', ['version_id' => $version->id])->assertJsonPath('result.structuredContent.meta.total', 1);
        $this->callTool($token, 'delete_vulnerabilities', ['id' => $id])->assertJsonPath('result.structuredContent.deleted', true);
    }

    public function test_governance_blocks_unapproved_publication_and_missing_override_scope(): void
    {
        $token = $this->token(['access_mcp', 'publish_versions', 'approve_versions']);
        $version = Version::factory()->create(['status' => VersionStatus::DRAFT, 'approval_status' => ApprovalStatus::PENDING]);
        $this->callTool($token, 'publish_version', ['id' => $version->id])->assertJsonPath('result.isError', true);
        $this->callTool($token, 'approve_version', ['id' => $version->id, 'override' => true, 'override_reason' => 'Test override'])->assertJsonPath('result.isError', true);
        $this->assertSame(ApprovalStatus::PENDING, $version->refresh()->approval_status);
    }

    public function test_role_downgrade_blocks_writes_despite_existing_token(): void
    {
        $user = User::factory()->create(['role' => UserRole::EDITOR]);
        $token = $this->token(['access_mcp', 'create_versions'], $user);
        $software = Software::factory()->create();
        $user->update(['role' => UserRole::VIEWER]);
        $this->callTool($token, 'create_versions', ['data' => ['software_id' => $software->id, 'version_number' => '1.0.0', 'release_date' => '2026-09-16']])->assertJsonStructure(['error']);
    }

    public function test_invalid_pagination_and_missing_records_are_tool_errors(): void
    {
        $token = $this->token();
        $this->callTool($token, 'list_versions', ['per_page' => 101])->assertJsonPath('result.isError', true);
        $this->callTool($token, 'show_versions', ['id' => 999999])->assertJsonPath('result.isError', true);
    }

    public function test_approval_override_publication_and_rejection_preserve_governance(): void
    {
        $token = $this->token();
        $version = Version::factory()->create(['version_number' => '3.0.0', 'status' => VersionStatus::DRAFT, 'approval_status' => ApprovalStatus::PENDING]);
        $this->callTool($token, 'approve_version', ['id' => $version->id])->assertJsonPath('result.isError', true);
        $this->callTool($token, 'approve_version', ['id' => $version->id, 'override' => true, 'override_reason' => 'External validation'])->assertJsonPath('result.isError', false);
        $this->assertSame(ApprovalStatus::APPROVED, $version->refresh()->approval_status);
        $this->callTool($token, 'publish_version', ['id' => $version->id])->assertJsonPath('result.isError', false);
        $this->assertSame(VersionStatus::PUBLISHED, $version->refresh()->status);
        $this->assertSame('3.0.0', $version->software->refresh()->current_version);
        $other = Version::factory()->create(['status' => VersionStatus::DRAFT, 'approval_status' => ApprovalStatus::PENDING]);
        $this->callTool($token, 'reject_version', ['id' => $other->id, 'reason' => 'Missing notes', 'reject_reason' => 'missing_content'])->assertJsonPath('result.isError', false);
        $this->assertSame(ApprovalStatus::REJECTED, $other->refresh()->approval_status);
        $this->callTool($token, 'update_versions', ['id' => $other->id, 'data' => ['version_number' => '1.0.1', 'release_date' => '2026-09-16']])->assertJsonPath('result.isError', false);
        $this->assertSame(ApprovalStatus::PENDING, $other->refresh()->approval_status);
    }

    public function test_four_eyes_cannot_be_overridden_with_an_admin_token(): void
    {
        $settings = app(GovernanceSettings::class);
        $settings->require_four_eyes_for_critical_releases = true;
        $settings->save();
        app()->forgetInstance(RuntimeSettings::class);
        $creator = User::factory()->admin()->create();
        $token = $this->token(['*'], $creator);
        $version = Version::factory()->create(['status' => VersionStatus::DRAFT, 'approval_status' => ApprovalStatus::PENDING, 'created_by' => $creator->id]);
        Vulnerability::factory()->create(['affected_version_id' => $version->id, 'severity' => VulnerabilitySeverity::CRITICAL, 'status' => VulnerabilityStatus::OPEN]);
        $this->callTool($token, 'approve_version', ['id' => $version->id, 'override' => true, 'override_reason' => 'Emergency'])->assertJsonPath('result.isError', true);
        $this->assertSame(ApprovalStatus::PENDING, $version->refresh()->approval_status);
        $reviewer = $this->token();
        $this->callTool($reviewer, 'approve_version', ['id' => $version->id, 'override' => true, 'override_reason' => 'Independent validation'])->assertJsonPath('result.isError', false);
    }

    public function test_owned_software_remains_editable_when_global_edit_permission_is_removed(): void
    {
        $user = User::factory()->create(['role' => UserRole::EDITOR]);
        $software = Software::factory()->create(['created_by' => $user->id]);
        $token = $this->token(['access_mcp', 'edit_software'], $user);
        $user->update(['role' => UserRole::VIEWER]);
        $this->callTool($token, 'update_software', ['id' => $software->id, 'data' => ['name' => 'Owned product', 'status' => 'active', 'compliance_status' => 'unknown']])
            ->assertJsonPath('result.structuredContent.data.name', 'Owned product');
    }
}
