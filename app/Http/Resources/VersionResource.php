<?php

namespace App\Http\Resources;

use App\Services\ReleaseReadinessService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VersionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $readiness = app(ReleaseReadinessService::class)->evaluate($this->resource);

        return [
            'id' => $this->id,
            'software_id' => $this->software_id,
            'software' => $this->whenLoaded('software', fn () => [
                'id' => $this->software?->id,
                'name' => $this->software?->name,
            ]),
            'version_number' => $this->version_number,
            'release_date' => $this->release_date?->toDateString(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'approval_status' => $this->approval_status?->value,
            'approval_status_label' => $this->approval_status?->label(),
            'rejection_reason' => $this->rejection_reason,
            'eol_date' => $this->eol_date?->toDateString(),
            'lts_date' => $this->lts_date?->toDateString(),
            'support_status' => $this->support_status?->value,
            'support_status_label' => $this->support_status?->label(),
            'readiness' => $readiness,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'text_contents' => TextContentResource::collection($this->whenLoaded('textContents')),
            'file_attachments' => FileAttachmentResource::collection($this->whenLoaded('fileAttachments')),
            'vulnerabilities' => VulnerabilityResource::collection($this->whenLoaded('vulnerabilities')),
        ];
    }
}
