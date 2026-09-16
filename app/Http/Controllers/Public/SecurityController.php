<?php

namespace App\Http\Controllers\Public;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicAdvisoryRequest;
use App\Http\Requests\PublicSecurityRequest;
use App\Http\Resources\PublicAdvisoryDetailResource;
use App\Http\Resources\PublicAdvisoryResource;
use App\Models\Vulnerability;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SecurityController extends Controller
{
    public function __invoke(PublicSecurityRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();
        $query = Vulnerability::query()
            ->with([
                'affectedVersion:id,software_id,version_number,status',
                'affectedVersion.software:id,name',
                'fixedVersion:id,version_number',
            ])
            ->whereHas('affectedVersion', fn ($query) => $query->where('status', VersionStatus::PUBLISHED->value))
            ->whereNot('status', VulnerabilityStatus::FALSE_POSITIVE->value)
            ->when($filters['software'] ?? null, fn ($query, int $softwareId) => $query->whereHas(
                'affectedVersion',
                fn ($query) => $query->where('software_id', $softwareId),
            ))
            ->when($filters['severity'] ?? null, fn ($query, string $severity) => $query->where('severity', $severity))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('cve_id', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('affectedVersion.software', fn ($query) => $query->where('name', 'like', "%{$search}%"));
                });
            });

        $summary = [
            'total' => (clone $query)->count(),
            'open' => (clone $query)->where('status', VulnerabilityStatus::OPEN->value)->count(),
            'critical' => (clone $query)->where('severity', VulnerabilitySeverity::CRITICAL->value)->count(),
            'high' => (clone $query)->where('severity', VulnerabilitySeverity::HIGH->value)->count(),
        ];

        $advisories = $query
            ->latest('published_date')
            ->paginate($request->integer('per_page', 12), page: $request->integer('page', 1))
            ->withQueryString();

        return PublicAdvisoryResource::collection($advisories)->additional([
            'summary' => $summary,
        ]);
    }

    public function show(PublicAdvisoryRequest $request, Vulnerability $vulnerability): JsonResponse
    {
        $vulnerability->load([
            'affectedVersion:id,software_id,version_number,status,release_date,support_status,eol_date,lts_date',
            'affectedVersion.software:id,name,description',
            'fixedVersion:id,version_number,release_date',
        ]);

        abort_unless(
            $vulnerability->affectedVersion?->status === VersionStatus::PUBLISHED
                && $vulnerability->status !== VulnerabilityStatus::FALSE_POSITIVE,
            404,
        );

        $affectedReleases = Vulnerability::query()
            ->where('cve_id', $vulnerability->cve_id)
            ->whereNot('status', VulnerabilityStatus::FALSE_POSITIVE->value)
            ->whereHas('affectedVersion', fn ($query) => $query->where('status', VersionStatus::PUBLISHED->value))
            ->with(['affectedVersion:id,software_id,version_number,release_date,support_status', 'affectedVersion.software:id,name'])
            ->get()
            ->map(fn (Vulnerability $advisory): array => [
                'id' => $advisory->affectedVersion?->id,
                'version' => $advisory->affectedVersion?->version_number,
                'release_date' => $advisory->affectedVersion?->release_date?->toDateString(),
                'support_status' => $advisory->affectedVersion?->support_status?->value,
                'product' => [
                    'id' => $advisory->affectedVersion?->software?->id,
                    'name' => $advisory->affectedVersion?->software?->name,
                ],
                'release_url' => $advisory->affectedVersion ? '/releases/'.$advisory->affectedVersion->id : null,
            ])
            ->unique(fn (array $release): string => ($release['product']['id'] ?? 'unknown').':'.($release['id'] ?? 'unknown'))
            ->values();

        return response()->json([
            'data' => [
                ...(new PublicAdvisoryDetailResource($vulnerability))->resolve($request),
                'affected_releases' => $affectedReleases,
                'remediation' => $vulnerability->fixedVersion ? [
                    'fixed_version' => $vulnerability->fixedVersion->version_number,
                    'release_url' => '/releases/'.$vulnerability->fixedVersion->id,
                ] : null,
            ],
        ]);
    }
}
