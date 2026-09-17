<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\DeploymentStatus;
use App\Enums\VersionStatus;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\Software;
use App\Models\Version;
use App\Services\ExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeploymentExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_deployment_logbook_can_be_filtered_and_exported_as_csv(): void
    {
        config()->set('filesystems.disks.exports', [
            'driver' => 'local',
            'root' => storage_path('app/exports'),
        ]);
        Storage::fake('exports');

        $software = Software::factory()->create();
        $version = Version::factory()->for($software)->create([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::APPROVED,
        ]);
        $environment = Environment::factory()->create(['code' => 'test']);
        $matchingDeployment = Deployment::factory()->create([
            'software_id' => $software->id,
            'version_id' => $version->id,
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::SUCCEEDED,
        ]);
        $matchingDeployment->forceFill(['created_at' => '2026-09-10 10:00:00'])->saveQuietly();

        Deployment::factory()->create([
            'software_id' => $software->id,
            'version_id' => $version->id,
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::FAILED,
        ]);

        $service = app(ExportService::class);
        $deployments = $service->filteredDeployments([
            'environment_id' => $environment->id,
            'status' => DeploymentStatus::SUCCEEDED->value,
            'date_from' => '2026-09-01',
            'date_to' => '2026-09-30',
        ]);

        $this->assertCount(1, $deployments);
        $this->assertTrue($deployments->first()->is($matchingDeployment));

        $path = $service->exportDeploymentsToCsv($deployments);
        $file = ltrim(Str::after($path, Storage::disk('exports')->path('')), DIRECTORY_SEPARATOR);

        Storage::disk('exports')->assertExists($file);
        $this->assertStringContainsString('Environment', Storage::disk('exports')->get($file));
        $this->assertStringContainsString($environment->name, Storage::disk('exports')->get($file));
    }
}
