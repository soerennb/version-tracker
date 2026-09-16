<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileAttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'version_id' => $this->version_id,
            'filename' => $this->filename,
            'file_extension' => pathinfo((string) $this->filename, PATHINFO_EXTENSION),
            'artifact_type' => $this->artifact_type,
            'platform' => $this->platform,
            'architecture' => $this->architecture,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'checksum' => $this->checksum,
            'checksum_algorithm' => $this->checksum_algorithm,
            'signature' => $this->signature,
            'verification_status' => $this->verification_status,
            'is_public' => $this->is_public,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
