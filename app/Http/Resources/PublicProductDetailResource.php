<?php

namespace App\Http\Resources;

use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\Version;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProductDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $versions = $this->versions;
        $currentVersion = $versions->first();
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
            'security' => [
                'open' => $openVulnerabilities->count(),
                'critical' => $openVulnerabilities->where('severity', VulnerabilitySeverity::CRITICAL)->count(),
                'high' => $openVulnerabilities->where('severity', VulnerabilitySeverity::HIGH)->count(),
                'status' => $openVulnerabilities->isEmpty() ? 'clear' : 'attention',
            ],
            'releases' => $versions->map(fn (Version $version): array => $this->release($version)),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function release(Version $version, bool $includeSecurity = false): array
    {
        $content = $version->textContents->first();
        $release = [
            'id' => $version->id,
            'version' => $version->version_number,
            'release_date' => $version->release_date?->toDateString(),
            'support_status' => $version->support_status?->value,
            'eol_date' => $version->eol_date?->toDateString(),
            'lts_date' => $version->lts_date?->toDateString(),
            'headline' => $content?->title,
            'summary' => str($content?->content)->limit(240)->toString(),
            'downloads' => $version->fileAttachments->map(fn ($attachment): array => [
                'id' => $attachment->id,
                'filename' => $attachment->filename,
                'mime_type' => $attachment->mime_type,
                'size' => $attachment->size,
                'download_url' => route('public.download', [$version, $attachment], false),
            ])->values(),
        ];

        if ($includeSecurity) {
            $release['open_vulnerabilities'] = $version->vulnerabilities
                ->where('status', VulnerabilityStatus::OPEN)
                ->count();
        }

        return $release;
    }
}
