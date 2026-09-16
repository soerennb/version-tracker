<?php

namespace App\Http\Controllers\Public;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Helpers\PublicLocale;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicOverviewRequest;
use App\Http\Resources\PublicAdvisoryResource;
use App\Http\Resources\PublicProductResource;
use App\Models\Software;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\RuntimeSettings;
use Illuminate\Http\JsonResponse;

class OverviewController extends Controller
{
    public function __invoke(PublicOverviewRequest $request, RuntimeSettings $runtimeSettings): JsonResponse
    {
        $productsEnabled = $runtimeSettings->publicFeatureEnabled('products');
        $securityEnabled = $runtimeSettings->publicFeatureEnabled('security');
        $products = $productsEnabled
            ? Software::query()
                ->whereHas('versions', fn ($query) => $query->where('status', VersionStatus::PUBLISHED->value))
                ->with(['versions' => fn ($query) => $query
                    ->where('status', VersionStatus::PUBLISHED->value)
                    ->with('textContents')
                    ->withCount(['vulnerabilities as open_vulnerabilities_count' => fn ($query) => $query
                        ->where('status', VulnerabilityStatus::OPEN->value)
                        ->whereNot('status', VulnerabilityStatus::FALSE_POSITIVE->value)])
                    ->latest('release_date')
                    ->limit(1)])
                ->withCount(['versions as published_versions_count' => fn ($query) => $query->where('status', VersionStatus::PUBLISHED->value)])
                ->orderBy('name')
                ->limit(4)
                ->get()
            : collect();

        $latestReleases = Version::query()
            ->where('status', VersionStatus::PUBLISHED->value)
            ->with([
                'software:id,name',
                'textContents',
            ])
            ->withCount(['vulnerabilities as open_vulnerabilities_count' => fn ($query) => $query
                ->where('status', VulnerabilityStatus::OPEN->value)
                ->whereNot('status', VulnerabilityStatus::FALSE_POSITIVE->value)])
            ->latest('release_date')
            ->limit(5)
            ->get()
            ->map(fn (Version $version): array => $this->releasePayload($version, $request))
            ->values();

        $securityQuery = Vulnerability::query()
            ->with([
                'affectedVersion:id,software_id,version_number,status',
                'affectedVersion.software:id,name',
                'fixedVersion:id,version_number',
            ])
            ->whereHas('affectedVersion', fn ($query) => $query->where('status', VersionStatus::PUBLISHED->value))
            ->whereNot('status', VulnerabilityStatus::FALSE_POSITIVE->value);

        $advisories = $securityEnabled
            ? (clone $securityQuery)
                ->latest('published_date')
                ->limit(5)
                ->get()
            : collect();

        return response()->json([
            'data' => [
                'generated_at' => now()->toISOString(),
                'locale' => $request->string('locale')->toString() ?: config('app.locale'),
                'metrics' => [
                    'products' => $productsEnabled
                        ? Software::query()
                            ->whereHas('versions', fn ($query) => $query->where('status', VersionStatus::PUBLISHED->value))
                            ->count()
                        : 0,
                    'releases' => Version::query()->where('status', VersionStatus::PUBLISHED->value)->count(),
                    'open_security' => $securityEnabled
                        ? (clone $securityQuery)->where('status', VulnerabilityStatus::OPEN->value)->count()
                        : 0,
                    'critical_security' => $securityEnabled
                        ? (clone $securityQuery)
                            ->where('status', VulnerabilityStatus::OPEN->value)
                            ->where('severity', VulnerabilitySeverity::CRITICAL->value)
                            ->count()
                        : 0,
                ],
                'products' => $productsEnabled ? PublicProductResource::collection($products)->resolve($request) : [],
                'latest_releases' => $latestReleases,
                'security' => $securityEnabled ? PublicAdvisoryResource::collection($advisories)->resolve($request) : [],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function releasePayload(Version $version, PublicOverviewRequest $request): array
    {
        $content = PublicLocale::content($version->textContents, $request);

        return [
            'id' => $version->id,
            'software_id' => $version->software_id,
            'software' => $version->software?->name,
            'version' => $version->version_number,
            'release_date' => $version->release_date?->toDateString(),
            'headline' => $content?->title,
            'summary' => str($content?->content)->limit(200)->toString(),
            'content_locale' => $content ? PublicLocale::languageValue($content) : null,
            'fallback_used' => PublicLocale::fallbackUsed($version->textContents, $request),
            'support_status' => $version->support_status?->value,
            'open_vulnerabilities' => (int) $version->open_vulnerabilities_count,
        ];
    }
}
