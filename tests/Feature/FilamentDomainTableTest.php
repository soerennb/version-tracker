<?php

namespace Tests\Feature;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Filament\Resources\Versions\Pages\ListVersions;
use App\Filament\Resources\Vulnerabilities\Pages\ListVulnerabilities;
use App\Models\Software;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentDomainTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_vulnerability_table_shows_product_and_version_instead_of_id(): void
    {
        $software = Software::factory()->create(['name' => 'Payments Core']);
        $version = Version::factory()->for($software)->create(['version_number' => '7.2.1']);
        $vulnerability = Vulnerability::factory()->for($version, 'affectedVersion')->create();

        Livewire::test(ListVulnerabilities::class)
            ->assertCanSeeTableRecords([$vulnerability])
            ->assertTableColumnStateSet('affectedVersion.software.name', 'Payments Core', record: $vulnerability)
            ->assertSee('7.2.1');
    }

    public function test_version_table_exposes_security_blocker_state(): void
    {
        $version = Version::factory()->create(['status' => VersionStatus::DRAFT]);
        Vulnerability::factory()->for($version, 'affectedVersion')->create([
            'status' => VulnerabilityStatus::OPEN,
            'severity' => VulnerabilitySeverity::CRITICAL,
        ]);

        Livewire::test(ListVersions::class)
            ->assertCanSeeTableRecords([$version])
            ->assertTableColumnStateSet('security_blockers_count', 1, record: $version)
            ->assertTableColumnFormattedStateSet(
                'security_blockers_count',
                __('filament.versions.security.blockers', ['count' => 1]),
                record: $version,
            );
    }
}
