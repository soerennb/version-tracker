<?php

namespace Tests\Feature;

use App\Http\Resources\FileAttachmentResource;
use App\Models\FileAttachment;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
