<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\VersionStatus;
use App\Http\Resources\FileAttachmentResource;
use App\Models\FileAttachment;
use App\Models\User;
use App\Models\Version;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FileAttachmentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_does_not_expose_internal_storage_path(): void
    {
        $attachment = FileAttachment::factory()->create([
            'file_path' => 'attachments/123/hidden.pdf',
            'filename' => 'hidden.pdf',
        ]);

        $payload = FileAttachmentResource::make($attachment)->toArray(request());

        $this->assertArrayNotHasKey('file_path', $payload);
        $this->assertSame('pdf', $payload['file_extension']);
    }

    public function test_api_upload_persists_public_artifact_metadata(): void
    {
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $user = User::factory()->create([
            'role' => UserRole::VIEWER,
            'abilities' => ['upload_files'],
        ]);
        Sanctum::actingAs($user);

        $version = Version::factory()->create(['status' => VersionStatus::PUBLISHED]);

        $response = $this->post('/api/versions/'.$version->id.'/file-attachments', [
            'file' => UploadedFile::fake()->create('release.pdf', 12, 'application/pdf'),
            'artifact_type' => 'installer',
            'platform' => 'linux',
            'architecture' => 'x86_64',
            'checksum' => 'abc123',
            'checksum_algorithm' => 'sha256',
            'verification_status' => 'verified',
            'is_public' => true,
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.artifact_type', 'installer')
            ->assertJsonPath('data.platform', 'linux')
            ->assertJsonPath('data.architecture', 'x86_64')
            ->assertJsonPath('data.checksum', 'abc123')
            ->assertJsonPath('data.verification_status', 'verified')
            ->assertJsonPath('data.is_public', true);
    }
}
