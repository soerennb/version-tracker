<?php

namespace App\Http\Resources;

use App\Enums\SupportStatus;
use App\Enums\VulnerabilityStatus;
use App\Helpers\PublicLocale;
use App\Models\Version;
use App\Services\RuntimeSettings;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latestVersion = $this->versions->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status?->value,
            'release_count' => $this->published_versions_count,
            'current_release' => $latestVersion ? [
                ...$this->release($latestVersion, $request),
                'open_vulnerabilities' => (int) ($latestVersion->open_vulnerabilities_count ?? 0),
            ] : null,
            'recommended_release' => $latestVersion ? [
                'id' => $latestVersion->id,
                'version' => $latestVersion->version_number,
            ] : null,
            'recommendation' => $latestVersion ? $this->recommendation($latestVersion) : [
                'code' => 'no_release',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function release(Version $version, Request $request): array
    {
        $content = PublicLocale::content($version->textContents, $request);

        return [
            'id' => $version->id,
            'version' => $version->version_number,
            'release_date' => $version->release_date?->toDateString(),
            'support_status' => $version->support_status?->value,
            'eol_date' => $version->eol_date?->toDateString(),
            'lts_date' => $version->lts_date?->toDateString(),
            'headline' => $content?->title,
            'content_locale' => $content ? PublicLocale::languageValue($content) : null,
            'fallback_used' => PublicLocale::fallbackUsed($version->textContents, $request),
        ];
    }

    /**
     * @return array{code:string}
     */
    private function recommendation(Version $version): array
    {
        $hasBlockingVulnerability = $version->relationLoaded('vulnerabilities')
            ? $version->vulnerabilities->contains(fn ($vulnerability): bool => $vulnerability->status === VulnerabilityStatus::OPEN
                && app(RuntimeSettings::class)->isBlockingSeverity($vulnerability->severity))
            : ((int) ($version->open_vulnerabilities_count ?? 0) > 0);

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
