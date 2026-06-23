<?php

namespace Tests\Feature;

use App\Enums\Language;
use App\Enums\VersionStatus;
use App\Models\FileAttachment;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\TextContent;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVersionCompareTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_published_versions_of_one_product_can_be_compared(): void
    {
        $product = Software::factory()->create();
        $dependency = Software::factory()->create(['name' => 'Runtime Core']);
        $left = Version::factory()->for($product)->create(['status' => VersionStatus::PUBLISHED, 'version_number' => '1.0.0']);
        $right = Version::factory()->for($product)->create(['status' => VersionStatus::PUBLISHED, 'version_number' => '2.0.0']);
        $dependencyV1 = Version::factory()->for($dependency)->create(['version_number' => '3.0.0']);
        $dependencyV2 = Version::factory()->for($dependency)->create(['version_number' => '4.0.0']);

        TextContent::factory()->for($left)->create(['language' => Language::DE, 'title' => 'Old notes']);
        TextContent::factory()->for($right)->create(['language' => Language::DE, 'title' => 'New notes']);
        FileAttachment::factory()->for($right)->create(['filename' => 'new.zip']);
        Vulnerability::factory()->for($left, 'affectedVersion')->create(['cve_id' => 'CVE-2026-1000']);
        SoftwareDependency::factory()->create([
            'software_id' => $product->id,
            'depends_on_software_id' => $dependency->id,
            'applies_to_version_id' => null,
            'min_version_id' => $dependencyV1->id,
            'max_version_id' => $dependencyV1->id,
            'dependency_type' => 'runtime',
        ]);
        SoftwareDependency::factory()->create([
            'software_id' => $product->id,
            'depends_on_software_id' => $dependency->id,
            'applies_to_version_id' => $right->id,
            'min_version_id' => $dependencyV2->id,
            'max_version_id' => $dependencyV2->id,
            'dependency_type' => 'runtime',
        ]);

        $this->getJson("/api/public/compare?left={$left->id}&right={$right->id}")
            ->assertOk()
            ->assertJsonPath('data.left.notes.0.title', 'Old notes')
            ->assertJsonPath('data.right.notes.0.title', 'New notes')
            ->assertJsonPath('data.left.advisories.0.cve_id', 'CVE-2026-1000')
            ->assertJsonPath('data.right.attachments.0.filename', 'new.zip')
            ->assertJsonPath('data.dependency_changes.0.status', 'changed')
            ->assertJsonPath('data.dependency_changes.0.before.min_version', '3.0.0')
            ->assertJsonPath('data.dependency_changes.0.after.min_version', '4.0.0');
    }

    public function test_compare_rejects_drafts_and_versions_from_different_products(): void
    {
        $published = Version::factory()->create(['status' => VersionStatus::PUBLISHED]);
        $draft = Version::factory()->create(['status' => VersionStatus::DRAFT]);
        $other = Version::factory()->create(['status' => VersionStatus::PUBLISHED]);

        $this->getJson("/api/public/compare?left={$published->id}&right={$draft->id}")->assertNotFound();
        $this->getJson("/api/public/compare?left={$published->id}&right={$other->id}")->assertNotFound();
    }
}
