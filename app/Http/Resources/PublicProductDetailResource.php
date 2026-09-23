<?php

namespace App\Http\Resources;

use App\Enums\SupportStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Helpers\PublicLocale;
use App\Models\FileAttachment;
use App\Models\Version;
use App\Services\RuntimeSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class PublicProductDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $versions = $this->versions->sortByDesc('release_date')->values();
        $currentVersion = $versions->first();
        $recommendedVersion = $this->recommendedVersion($versions) ?? $currentVersion;
        $vulnerabilities = $versions->flatMap->vulnerabilities;
        $openVulnerabilities = $vulnerabilities->where('status', VulnerabilityStatus::OPEN);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status?->value,
            'license_type' => $this->license_type,
            'compliance_status' => $this->compliance_status?->value,
            'current_release' => $currentVersion ? $this->release($currentVersion, true) : null,
            'recommended_release' => $recommendedVersion ? $this->release($recommendedVersion, true) : null,
            'recommendation' => $recommendedVersion ? $this->recommendation($recommendedVersion) : ['code' => 'no_release'],
            'security' => [
                'scope' => 'all_published_releases',
                'open' => $openVulnerabilities->count(),
                'critical' => $openVulnerabilities->where('severity', VulnerabilitySeverity::CRITICAL)->count(),
                'high' => $openVulnerabilities->where('severity', VulnerabilitySeverity::HIGH)->count(),
                'status' => $openVulnerabilities->isEmpty() ? 'clear' : 'attention',
            ],
            'dependencies' => $this->dependenciesOutgoing->map(fn ($dependency): array => [
                'id' => $dependency->id,
                'name' => $dependency->dependsOnSoftware?->name,
                'type' => $dependency->dependency_type,
                'applies_to_version' => $dependency->applies_to_version_id,
                'minimum_version' => $dependency->minVersion?->version_number,
                'maximum_version' => $dependency->maxVersion?->version_number,
            ])->values(),
            'release_count' => $versions->count(),
            'releases' => $versions->map(fn (Version $version): array => $this->release($version, true)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function release(Version $version, bool $includeSecurity = false): array
    {
        $request = request();
        $content = PublicLocale::content($version->textContents, $request);
        $release = [
            'id' => $version->id,
            'version' => $version->version_number,
            'release_date' => $version->release_date?->toDateString(),
            'support_status' => $version->support_status?->value,
            'eol_date' => $version->eol_date?->toDateString(),
            'lts_date' => $version->lts_date?->toDateString(),
            'headline' => $content?->title,
            'summary' => str($content?->content)->limit(240)->toString(),
            'content_locale' => $content ? PublicLocale::languageValue($content) : null,
            'fallback_used' => PublicLocale::fallbackUsed($version->textContents, $request),
            'downloads' => $version->fileAttachments
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
                    'download_url' => route('public.download', [$version, $attachment], false),
                ])->values(),
            'composition' => $version->composition ? ReleaseCompositionResource::make($version->composition) : null,
        ];

        if ($includeSecurity) {
            $release['open_vulnerabilities'] = $version->vulnerabilities
                ->where('status', VulnerabilityStatus::OPEN)
                ->count();
        }

        return $release;
    }

    private function recommendedVersion(Collection $versions): ?Version
    {
        return $versions->first(function (Version $version): bool {
            $hasBlockingVulnerability = $version->vulnerabilities->contains(fn ($vulnerability): bool => $vulnerability->status === VulnerabilityStatus::OPEN
                && app(RuntimeSettings::class)->isBlockingSeverity($vulnerability->severity));

            return ! $hasBlockingVulnerability
                && in_array($version->support_status, [SupportStatus::SUPPORTED, SupportStatus::MAINTENANCE], true)
                && ($version->eol_date === null || $version->eol_date->isFuture());
        });
    }

    /**
     * @return array{code:string}
     */
    private function recommendation(Version $version): array
    {
        $hasBlockingVulnerability = $version->vulnerabilities->contains(fn ($vulnerability): bool => $vulnerability->status === VulnerabilityStatus::OPEN
            && app(RuntimeSettings::class)->isBlockingSeverity($vulnerability->severity));

        if ($hasBlockingVulnerability) {
            return ['code' => 'security_update'];
        }

        return match ($version->support_status) {
            SupportStatus::SUPPORTED => ['code' => 'recommended'],
            SupportStatus::MAINTENANCE => ['code' => 'maintenance'],
            SupportStatus::DEPRECATED, SupportStatus::EOL => ['code' => 'migrate'],
            default => ['code' => 'review'],
        };
    }
}
