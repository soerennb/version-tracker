<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\Language;
use App\Enums\SoftwareStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Filament\Pages\AnalyticsDashboard;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\TextContent;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\AdminWorkQueueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkQueueServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_queues_surface_each_actionable_category(): void
    {
        $product = Software::factory()->create(['status' => SoftwareStatus::ACTIVE]);
        $pending = Version::factory()->for($product)->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::PENDING,
            'release_date' => today(),
        ]);
        TextContent::factory()->for($pending)->create(['language' => Language::DE]);

        $eol = Version::factory()->for($product)->create([
            'status' => VersionStatus::PUBLISHED,
            'approval_status' => ApprovalStatus::APPROVED,
            'eol_date' => today()->addDays(30),
        ]);
        $blocker = Vulnerability::factory()->for($eol, 'affectedVersion')->create([
            'status' => VulnerabilityStatus::OPEN,
            'severity' => VulnerabilitySeverity::CRITICAL,
        ]);

        $inactiveDependency = Software::factory()->create(['status' => SoftwareStatus::INACTIVE]);
        $broken = SoftwareDependency::factory()->create([
            'software_id' => $product->id,
            'depends_on_software_id' => $inactiveDependency->id,
            'min_version_id' => null,
            'max_version_id' => null,
        ]);

        $queues = app(AdminWorkQueueService::class)->queues();

        $this->assertTrue($queues['due_reviews']->contains($pending));
        $this->assertTrue($queues['pending_approvals']->contains($pending));
        $this->assertTrue($queues['security_blockers']->contains($blocker));
        $this->assertTrue($queues['eol_soon']->contains($eol));
        $this->assertTrue($queues['incomplete_notes']->contains($pending));
        $this->assertTrue($queues['broken_dependencies']->contains($broken));
    }

    public function test_admin_dashboard_renders_operational_queue_labels(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get(AnalyticsDashboard::getUrl())
            ->assertOk()
            ->assertSee(__('filament.work_queue.due_reviews'))
            ->assertSee(__('filament.work_queue.security_blockers'))
            ->assertSee(__('filament.work_queue.broken_dependencies'));
    }
}
