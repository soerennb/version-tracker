<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\Language;
use App\Enums\SupportStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Filament\Pages\VersionApproval;
use App\Models\AuditLog;
use App\Models\FileAttachment;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\TextContent;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\ApprovalCockpitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class ApprovalCockpitTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_cockpit_builds_review_context(): void
    {
        $reviewer = User::factory()->admin()->create(['name' => 'Release Reviewer']);
        $software = Software::factory()->create(['name' => 'Core API']);
        $version = Version::factory()->for($software)->create([
            'version_number' => '1.2.0',
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::PENDING,
            'support_status' => SupportStatus::SUPPORTED,
        ]);
        $dependencyTarget = Software::factory()->create(['name' => 'Auth Service']);
        SoftwareDependency::factory()->create([
            'software_id' => $software->id,
            'depends_on_software_id' => $dependencyTarget->id,
            'min_version_id' => null,
            'max_version_id' => null,
            'dependency_type' => 'runtime',
        ]);
        SoftwareDependency::factory()->create([
            'software_id' => Software::factory()->create(['name' => 'Frontend'])->id,
            'depends_on_software_id' => $software->id,
            'min_version_id' => null,
            'max_version_id' => null,
            'dependency_type' => 'runtime',
        ]);
        TextContent::factory()->for($version)->create([
            'title' => 'Release notes DE',
            'language' => Language::DE,
        ]);
        TextContent::factory()->for($version)->create([
            'title' => 'Release notes EN',
            'language' => Language::EN,
        ]);
        FileAttachment::factory()->for($version)->create(['filename' => 'sbom.json']);
        Vulnerability::factory()->for($version, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-2222',
            'severity' => VulnerabilitySeverity::HIGH,
            'status' => VulnerabilityStatus::OPEN,
            'cvss_score' => 8.7,
        ]);
        AuditLog::factory()->for($reviewer, 'user')->create([
            'action' => 'update',
            'model_type' => Version::class,
            'model_id' => $version->id,
            'old_values' => ['version_number' => '1.1.0'],
            'new_values' => ['version_number' => '1.2.0'],
        ]);

        $cockpit = app(ApprovalCockpitService::class)->build($version);

        $this->assertSame('Core API', $cockpit['version']->software->name);
        $this->assertSame(2, $cockpit['release_notes']->count());
        $this->assertSame('sbom.json', $cockpit['attachments']->first()->filename);
        $this->assertSame('CVE-2026-2222', $cockpit['vulnerabilities']->first()->cve_id);
        $this->assertSame('Auth Service', $cockpit['dependencies']->first()->dependsOnSoftware->name);
        $this->assertSame('Frontend', $cockpit['impact'][0]['software']['name']);
        $this->assertSame('1.2.0', $cockpit['audit_changes']->first()['changes']['version_number']['new']);
    }

    public function test_approval_page_selects_pending_version_for_cockpit(): void
    {
        $pendingVersion = Version::factory()->create([
            'approval_status' => ApprovalStatus::PENDING,
        ]);
        $approvedVersion = Version::factory()->create([
            'approval_status' => ApprovalStatus::APPROVED,
        ]);

        $page = new VersionApproval;
        $page->mount();

        $this->assertSame($pendingVersion->id, $page->selectedVersionId);

        $page->selectVersion($approvedVersion);

        $method = new ReflectionMethod(VersionApproval::class, 'selectedVersion');
        $method->setAccessible(true);

        $this->assertNull($method->invoke($page));
    }
}
