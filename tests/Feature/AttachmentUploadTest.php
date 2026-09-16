<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AttachmentUpload;
use App\Models\FileAttachment;
use App\Models\User;
use App\Models\Version;
use App\Services\AttachmentUploadService;
use App\Services\FileAttachmentService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AttachmentUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Storage::fake('public');
        config(['filesystems.default' => 'public']);
    }

    protected function token(array $abilities = ['access_mcp', 'upload_files', 'edit_files', 'delete_files', 'download_files']): string
    {
        return User::factory()->create(['role' => UserRole::ADMIN])->createToken('Upload', $abilities)->plainTextToken;
    }

    protected function callTool(string $token, string $name, array $arguments = []): TestResponse
    {
        auth()->forgetGuards();

        return $this->withToken($token)->postJson('/mcp/versiontracker', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => $name, 'arguments' => $arguments]]);
    }

    protected function prepare(string $token, Version $version, ?FileAttachment $attachment = null): string
    {
        $response = $this->callTool($token, 'prepare_attachment_upload', ['version_id' => $version->id, 'filename' => 'release.txt', 'metadata' => ['artifact_type' => 'notes'], 'attachment_id' => $attachment?->id])->assertJsonPath('result.isError', false);

        return $response->json('result.structuredContent.upload_id');
    }

    protected function transfer(string $token, string $id, ?UploadedFile $file = null): TestResponse
    {
        auth()->forgetGuards();

        return $this->withToken($token)->post('/mcp/uploads/'.$id, ['file' => $file ?? UploadedFile::fake()->createWithContent('release.txt', 'Release content')], ['Accept' => 'application/json']);
    }

    public function test_upload_completion_metadata_crud_and_idempotency(): void
    {
        $token = $this->token();
        $version = Version::factory()->create();
        $id = $this->prepare($token, $version);
        $this->transfer($token, $id)->assertOk();
        $response = $this->callTool($token, 'complete_attachment_upload', ['upload_id' => $id])->assertJsonPath('result.isError', false);
        $attachmentId = $response->json('result.structuredContent.data.id');
        $attachment = FileAttachment::query()->findOrFail($attachmentId);
        Storage::disk('public')->assertExists($attachment->file_path);
        Storage::disk('local')->assertMissing('mcp-uploads/'.$id.'/release.txt');
        $this->assertSame('notes', $attachment->artifact_type);
        $this->callTool($token, 'complete_attachment_upload', ['upload_id' => $id])->assertJsonPath('result.structuredContent.data.id', $attachmentId);
        $this->assertDatabaseCount('file_attachments', 1);
        $this->callTool($token, 'list_attachments', ['version_id' => $version->id])->assertJsonPath('result.structuredContent.meta.total', 1);
        $this->callTool($token, 'show_attachments', ['id' => $attachmentId])->assertJsonPath('result.isError', false);
        $this->callTool($token, 'update_attachments', ['id' => $attachmentId, 'data' => ['platform' => 'linux']])->assertJsonPath('result.structuredContent.data.platform', 'linux');
        $this->callTool($token, 'delete_attachments', ['id' => $attachmentId])->assertJsonPath('result.structuredContent.deleted', true);
        Storage::disk('public')->assertMissing($attachment->file_path);
    }

    public function test_replacement_preserves_record_and_uses_edit_permission(): void
    {
        $token = $this->token(['access_mcp', 'edit_files']);
        $version = Version::factory()->create();
        $attachment = FileAttachment::factory()->for($version)->create(['file_path' => 'attachments/old.txt']);
        Storage::disk('public')->put($attachment->file_path, 'Old content');
        $id = $this->prepare($token, $version, $attachment);
        $this->transfer($token, $id)->assertOk();
        $this->callTool($token, 'complete_attachment_upload', ['upload_id' => $id])->assertJsonPath('result.structuredContent.data.id', $attachment->id);
        $this->assertDatabaseCount('file_attachments', 1);
        $this->assertNotSame('attachments/old.txt', $attachment->refresh()->file_path);
        Storage::disk('public')->assertExists($attachment->file_path);
    }

    public function test_transfer_cannot_be_repeated_or_used_with_another_token(): void
    {
        $token = $this->token();
        $id = $this->prepare($token, Version::factory()->create());
        $this->transfer($this->token(), $id)->assertForbidden();
        $this->transfer($token, $id)->assertOk();
        $this->transfer($token, $id)->assertUnprocessable();
    }

    public function test_incomplete_expired_and_revoked_uploads_fail(): void
    {
        $token = $this->token();
        $id = $this->prepare($token, Version::factory()->create());
        $this->callTool($token, 'complete_attachment_upload', ['upload_id' => $id])->assertJsonPath('result.isError', true);
        AttachmentUpload::query()->findOrFail($id)->update(['expires_at' => now()->subMinute()]);
        $this->transfer($token, $id)->assertUnprocessable();
        $this->callTool($token, 'complete_attachment_upload', ['upload_id' => $id])->assertJsonPath('result.isError', true);
        PersonalAccessToken::query()->delete();
        $this->transfer($token, $id)->assertUnauthorized();
    }

    public function test_file_type_and_size_validation_does_not_consume_upload(): void
    {
        $token = $this->token();
        $id = $this->prepare($token, Version::factory()->create());
        $this->transfer($token, $id, UploadedFile::fake()->create('malicious.exe', 1, 'application/x-msdownload'))->assertUnprocessable();
        $this->transfer($token, $id, UploadedFile::fake()->create('large.txt', 10241, 'text/plain'))->assertUnprocessable();
        $this->assertNull(AttachmentUpload::query()->findOrFail($id)->temporary_path);
        $this->transfer($token, $id)->assertOk();
    }

    public function test_replacement_cannot_target_another_version(): void
    {
        $token = $this->token();
        $attachment = FileAttachment::factory()->create();
        $this->callTool($token, 'prepare_attachment_upload', ['version_id' => Version::factory()->create()->id, 'filename' => 'release.txt', 'attachment_id' => $attachment->id])->assertJsonPath('result.isError', true);
    }

    public function test_pruning_removes_expired_and_orphaned_temporary_files(): void
    {
        $token = $this->token();
        $id = $this->prepare($token, Version::factory()->create());
        $this->transfer($token, $id)->assertOk();
        AttachmentUpload::query()->findOrFail($id)->update(['expires_at' => now()->subMinute()]);
        Storage::disk('local')->put('mcp-uploads/orphan/file.txt', 'Orphan');
        app(AttachmentUploadService::class)->prune();
        $this->assertDatabaseMissing('attachment_uploads', ['id' => $id]);
        $this->assertSame([], Storage::disk('local')->allFiles('mcp-uploads'));
    }

    public function test_storage_failure_keeps_existing_attachment_and_file(): void
    {
        $attachment = FileAttachment::factory()->create(['file_path' => 'attachments/old.txt']);
        $public = Storage::disk('public');
        $public->put('attachments/old.txt', 'Old content');
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('putFileAs')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('public')->andReturn($disk);
        try {
            app(FileAttachmentService::class)->store($attachment->version, UploadedFile::fake()->createWithContent('release.txt', 'Replacement'), [], $attachment);
            $this->fail('Expected storage failure.');
        } catch (\RuntimeException) {
            $this->assertSame('attachments/old.txt', $attachment->refresh()->file_path);
            $this->assertTrue($public->exists('attachments/old.txt'));
        }
    }

    public function test_database_failure_during_completion_keeps_original_and_cleans_new_file(): void
    {
        $token = $this->token();
        $version = Version::factory()->create();
        $attachment = FileAttachment::factory()->for($version)->create(['file_path' => 'attachments/original.txt']);
        Storage::disk('public')->put('attachments/original.txt', 'Original');
        $id = $this->prepare($token, $version, $attachment);
        $this->transfer($token, $id)->assertOk();
        AttachmentUpload::updating(function (AttachmentUpload $upload): void {
            if ($upload->completed_at) {
                throw new \RuntimeException('Simulated completion failure');
            }
        });
        $this->callTool($token, 'complete_attachment_upload', ['upload_id' => $id])->assertJsonPath('result.isError', true);
        $this->assertSame('attachments/original.txt', $attachment->refresh()->file_path);
        Storage::disk('public')->assertExists('attachments/original.txt');
        $this->assertSame(['attachments/original.txt'], Storage::disk('public')->allFiles('attachments'));
        Storage::disk('local')->assertExists('mcp-uploads/'.$id.'/release.txt');
        $this->assertNull(AttachmentUpload::query()->findOrFail($id)->completed_at);
    }

    public function test_shallow_rest_attachment_routes_support_token_scopes(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('REST', ['access_rest', 'upload_files', 'edit_files', 'delete_files', 'download_files'])->plainTextToken;
        $version = Version::factory()->create();
        auth()->forgetGuards();
        $response = $this->withToken($token)->post('/api/versions/'.$version->id.'/file-attachments', ['file' => UploadedFile::fake()->createWithContent('notes.txt', 'Notes')], ['Accept' => 'application/json'])->assertCreated();
        $id = $response->json('data.id');
        auth()->forgetGuards();
        $this->withToken($token)->getJson('/api/file-attachments/'.$id)->assertOk();
        auth()->forgetGuards();
        $this->withToken($token)->patchJson('/api/file-attachments/'.$id, ['platform' => 'linux'])->assertOk()->assertJsonPath('data.platform', 'linux');
        auth()->forgetGuards();
        $this->withToken($token)->deleteJson('/api/file-attachments/'.$id)->assertNoContent();
    }

    public function test_same_user_cannot_complete_upload_with_a_different_token(): void
    {
        $user = User::factory()->admin()->create();
        $first = $user->createToken('First', ['access_mcp', 'upload_files'])->plainTextToken;
        $second = $user->createToken('Second', ['access_mcp', 'upload_files'])->plainTextToken;
        $id = $this->prepare($first, Version::factory()->create());
        $this->transfer($first, $id)->assertOk();
        $this->callTool($second, 'complete_attachment_upload', ['upload_id' => $id])->assertJsonPath('result.isError', true);
    }
}
