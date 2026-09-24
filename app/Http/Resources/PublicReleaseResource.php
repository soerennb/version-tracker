<?php

namespace App\Http\Resources;

use App\Enums\VulnerabilityStatus;
use App\Helpers\PublicLocale;
use App\Models\FileAttachment;
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
            'preferred_note' => $this->preferredNote($request),
            'content_locale' => $this->preferredNote($request)['language'] ?? null,
            'fallback_used' => PublicLocale::fallbackUsed($this->textContents, $request),
            'notes' => $this->textContents->map(fn ($content): array => [
                'language' => $content->language?->value,
                'language_label' => $content->language?->nativeLabel(),
                'title' => $content->title,
                'content' => $content->content,
            ])->values(),
            'downloads' => $this->fileAttachments
                ->filter(fn (FileAttachment $attachment): bool => $attachment->is_public !== false)
                ->map(fn (FileAttachment $attachment): array => [
                    'id' => $attachment->id,
                    'filename' => $attachment->filename,
                    'artifact_type' => $attachment->artifact_type,
                    'platform' => $attachment->platform,
                    'architecture' => $attachment->architecture,
                    'mime_type' => $attachment->mime_type,
                    'size' => $attachment->size,
                    'checksum' => $attachment->checksum,
                    'checksum_algorithm' => $attachment->checksum_algorithm,
                    'verification_status' => $attachment->verification_status,
                    'download_url' => route('public.download', [$this->resource, $attachment], false),
                ])->values(),
            'security' => [
                'open' => $this->vulnerabilities->where('status', VulnerabilityStatus::OPEN)->count(),
                'has_fix' => $this->vulnerabilities->contains(fn ($vulnerability): bool => $vulnerability->fixedVersion !== null),
            ],
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
                'exploitability' => $vulnerability->exploitability?->value,
            ])->values(),
            'sources' => $this->whenLoaded('sources', fn () => $this->sources->map(fn ($source): array => [
                'provider' => $source->provider,
                'kind' => $source->source_kind?->value,
                'tag_name' => $source->tag_name,
                'name' => $source->name,
                'source_url' => $source->source_url,
                'source_updated_at' => $source->source_updated_at?->toISOString(),
                'is_prerelease' => $source->is_prerelease,
            ])->values()),
            'composition' => $this->composition ? ReleaseCompositionResource::make($this->composition) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function preferredNote(Request $request): array
    {
        $note = PublicLocale::content($this->textContents, $request);

        return $note ? [
            'language' => PublicLocale::languageValue($note),
            'title' => $note->title,
            'content' => $note->content,
        ] : [];
    }
}
