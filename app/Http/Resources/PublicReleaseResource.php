<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicReleaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->software->id,
                'name' => $this->software->name,
            ],
            'version' => $this->version_number,
            'release_date' => $this->release_date?->toDateString(),
            'support_status' => $this->support_status?->value,
            'eol_date' => $this->eol_date?->toDateString(),
            'lts_date' => $this->lts_date?->toDateString(),
            'notes' => $this->textContents->map(fn ($content): array => [
                'language' => $content->language?->value,
                'language_label' => $content->language?->nativeLabel(),
                'title' => $content->title,
                'content' => $content->content,
            ])->values(),
            'downloads' => $this->fileAttachments->map(fn ($attachment): array => [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'download_url' => route('public.download', [$this->resource, $attachment], false),
            ])->values(),
            'advisories' => $this->vulnerabilities->map(fn ($vulnerability): array => [
                'id' => $vulnerability->id,
                'cve_id' => $vulnerability->cve_id,
                'severity' => $vulnerability->severity?->value,
                'cvss_score' => $vulnerability->cvss_score,
                'description' => $vulnerability->description,
                'source' => $vulnerability->source,
                'source_url' => $vulnerability->source_url,
                'affected_range' => $vulnerability->affected_range,
                'status' => $vulnerability->status?->value,
                'fixed_version' => $vulnerability->fixedVersion?->version_number,
                'published_date' => $vulnerability->published_date?->toDateString(),
            ])->values(),
        ];
    }
}
