<?php

namespace Tests\Feature;

use App\Enums\ExploitabilityStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Filament\Pages\SecurityDashboard;
use App\Models\Software;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class SecurityDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_security_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $this->assertTrue(SecurityDashboard::canAccess());
    }

    public function test_guest_cannot_access_security_dashboard(): void
    {
        $this->assertFalse(SecurityDashboard::canAccess());
    }

    public function test_security_dashboard_prioritizes_actionable_risk(): void
    {
        $software = Software::factory()->create();
        $affectedVersion = Version::factory()->for($software)->create([
            'status' => VersionStatus::PUBLISHED,
            'eol_date' => now()->addDays(30),
        ]);
        $fixedVersion = Version::factory()->for($software)->create([
            'version_number' => '2.0.0',
            'eol_date' => null,
        ]);

        Vulnerability::factory()->for($affectedVersion, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-1001',
            'severity' => VulnerabilitySeverity::CRITICAL,
            'cvss_score' => 9.8,
            'status' => VulnerabilityStatus::OPEN,
            'fixed_version_id' => $fixedVersion->id,
            'exploitability' => ExploitabilityStatus::PROOF_OF_CONCEPT,
        ]);

        Vulnerability::factory()->for($affectedVersion, 'affectedVersion')->create([
            'severity' => VulnerabilitySeverity::HIGH,
            'status' => VulnerabilityStatus::FIXED,
        ]);

        $viewData = $this->dashboardViewData();

        $this->assertSame(1, $viewData['openCriticalOrHigh']);
        $this->assertSame(1, $viewData['fixAvailable']);
        $this->assertSame(1, $viewData['eolRiskCount']);
        $this->assertSame(1, $viewData['affectedSoftwareCount']);
        $this->assertSame('CVE-2026-1001', $viewData['priorityFindings']->first()->cve_id);
        $this->assertSame(1, $viewData['severityBreakdown'][VulnerabilitySeverity::CRITICAL->value]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dashboardViewData(): array
    {
        $method = new ReflectionMethod(SecurityDashboard::class, 'getViewData');
        $method->setAccessible(true);

        return $method->invoke(new SecurityDashboard);
    }
}
