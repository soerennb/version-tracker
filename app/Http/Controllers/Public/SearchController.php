<?php

namespace App\Http\Controllers\Public;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilityStatus;
use App\Helpers\PublicLocale;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSearchRequest;
use App\Http\Resources\PublicAdvisoryResource;
use App\Http\Resources\PublicProductResource;
use App\Models\Software;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\RuntimeSettings;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __invoke(PublicSearchRequest $request, RuntimeSettings $runtimeSettings): JsonResponse
    {
        $search = $request->string('q')->trim()->toString();
        $types = collect($request->input('types', ['products', 'releases', 'security']))
            ->filter(fn (string $type): bool => match ($type) {
                'products' => $runtimeSettings->publicFeatureEnabled('products'),
                'releases' => $runtimeSettings->publicFeatureEnabled('catalog'),
                'security' => $runtimeSettings->publicFeatureEnabled('security'),
                default => false,
            })
            ->values()
            ->all();
        $data = [];

        if (in_array('products', $types, true)) {
            $products = Software::query()
                ->whereHas('versions', fn ($query) => $query->where('status', VersionStatus::PUBLISHED->value))
                ->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                })
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
                ->limit(8)
                ->get();

            $data['products'] = PublicProductResource::collection($products)->resolve($request);
        }

        if (in_array('releases', $types, true)) {
            $releases = Version::query()
                ->where('status', VersionStatus::PUBLISHED->value)
                ->where(function ($query) use ($search): void {
                    $query->where('version_number', 'like', "%{$search}%")
                        ->orWhereHas('software', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('textContents', fn ($query) => $query
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('content', 'like', "%{$search}%"));
                })
                ->with(['software:id,name', 'textContents'])
                ->withCount(['vulnerabilities as open_vulnerabilities_count' => fn ($query) => $query
                    ->where('status', VulnerabilityStatus::OPEN->value)
                    ->whereNot('status', VulnerabilityStatus::FALSE_POSITIVE->value)])
                ->latest('release_date')
                ->limit(8)
                ->get();

            $data['releases'] = $releases->map(function (Version $version) use ($request): array {
                $content = PublicLocale::content($version->textContents, $request);

                return [
                    'id' => $version->id,
                    'software_id' => $version->software_id,
                    'software' => $version->software?->name,
                    'version' => $version->version_number,
                    'release_date' => $version->release_date?->toDateString(),
                    'headline' => $content?->title,
                    'summary' => str($content?->content)->limit(200)->toString(),
                    'support_status' => $version->support_status?->value,
                    'open_vulnerabilities' => (int) $version->open_vulnerabilities_count,
                    'content_locale' => $content ? PublicLocale::languageValue($content) : null,
                    'fallback_used' => PublicLocale::fallbackUsed($version->textContents, $request),
                ];
            })->values();
        }

        if (in_array('security', $types, true)) {
            $advisories = Vulnerability::query()
                ->whereNot('status', VulnerabilityStatus::FALSE_POSITIVE->value)
                ->where(function ($query) use ($search): void {
                    $query->where('cve_id', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('affectedVersion.software', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                })
                ->whereHas('affectedVersion', fn ($query) => $query->where('status', VersionStatus::PUBLISHED->value))
                ->with(['affectedVersion:id,software_id,version_number,status', 'affectedVersion.software:id,name', 'fixedVersion:id,version_number'])
                ->latest('published_date')
                ->limit(8)
                ->get();

            $data['security'] = PublicAdvisoryResource::collection($advisories)->resolve($request);
        }

        return response()->json([
            'data' => $data,
            'meta' => [
                'query' => $search,
                'types' => $types,
                'locale' => $request->string('locale')->toString() ?: config('app.locale'),
            ],
        ]);
    }
}
