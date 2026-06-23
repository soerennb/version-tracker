<?php

namespace Tests\Feature;

use App\Enums\VersionStatus;
use App\Models\FileAttachment;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicDownloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_attachment_of_published_release_can_be_downloaded(): void
    {
        Storage::fake('local');
        $version = Version::factory()->create(['status' => VersionStatus::PUBLISHED]);
        $attachment = FileAttachment::factory()->for($version)->create([
            'filename' => 'release.zip',
            'file_path' => "attachments/{$version->id}/release.zip",
            'mime_type' => 'application/zip',
        ]);
        Storage::disk('local')->put($attachment->file_path, 'release-content');

        $this->get(route('public.download', [$version, $attachment]))
            ->assertOk()
            ->assertHeader('content-type', 'application/zip')
            ->assertDownload('release.zip');
    }

    public function test_draft_and_mismatched_attachments_are_not_public(): void
    {
        Storage::fake('local');
        $draft = Version::factory()->create(['status' => VersionStatus::DRAFT]);
        $draftAttachment = FileAttachment::factory()->for($draft)->create(['file_path' => 'attachments/draft.zip']);
        Storage::disk('local')->put($draftAttachment->file_path, 'secret');

        $published = Version::factory()->create(['status' => VersionStatus::PUBLISHED]);

        $this->get(route('public.download', [$draft, $draftAttachment]))->assertNotFound();
        $this->get(route('public.download', [$published, $draftAttachment]))->assertNotFound();
    }

    public function test_missing_and_unsafe_paths_are_not_downloaded(): void
    {
        Storage::fake('local');
        $version = Version::factory()->create(['status' => VersionStatus::PUBLISHED]);
        $missing = FileAttachment::factory()->for($version)->create(['file_path' => 'attachments/missing.zip']);
        $unsafe = FileAttachment::factory()->for($version)->create(['file_path' => '../outside.txt']);

        $this->get(route('public.download', [$version, $missing]))->assertNotFound();
        $this->get(route('public.download', [$version, $unsafe]))->assertNotFound();
    }
}
