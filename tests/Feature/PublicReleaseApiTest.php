<?php

namespace Tests\Feature;

use App\Enums\Language;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilityStatus;
use App\Models\FileAttachment;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicReleaseApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_release_includes_localized_notes_attachments_and_advisories(): void
    {
        $release = Version::factory()->for(Software::factory())->create([
            'status' => VersionStatus::PUBLISHED,
            'version_number' => '5.2.0',
        ]);
        TextContent::factory()->for($release)->create(['language' => Language::DE, 'title' => 'Deutsch']);
        TextContent::factory()->for($release)->create(['language' => Language::EN, 'title' => 'English']);
        FileAttachment::factory()->for($release)->create([
            'filename' => 'release.zip',
            'file_path' => 'private/release.zip',
        ]);
        Vulnerability::factory()->for($release, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-12345',
            'status' => VulnerabilityStatus::OPEN,
        ]);
        Vulnerability::factory()->for($release, 'affectedVersion')->create([
            'cve_id' => 'CVE-2026-HIDDEN',
            'status' => VulnerabilityStatus::FALSE_POSITIVE,
        ]);

        $this->getJson("/api/public/releases/{$release->id}")
            ->assertOk()
            ->assertJsonPath('data.version', '5.2.0')
            ->assertJsonCount(2, 'data.notes')
            ->assertJsonPath('data.downloads.0.filename', 'release.zip')
            ->assertJsonPath('data.advisories.0.cve_id', 'CVE-2026-12345')
            ->assertJsonMissing(['cve_id' => 'CVE-2026-HIDDEN'])
            ->assertJsonMissing(['file_path' => 'private/release.zip']);
    }

    public function test_draft_release_is_not_publicly_available(): void
    {
        $release = Version::factory()->create(['status' => VersionStatus::DRAFT]);

        $this->getJson("/api/public/releases/{$release->id}")->assertNotFound();
    }
}
