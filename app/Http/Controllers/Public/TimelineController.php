<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublicTimelineRequest;
use App\Models\Software;
use App\Models\Version;
use Illuminate\Http\JsonResponse;

class TimelineController extends Controller
{
    public function index(PublicTimelineRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $softwareFilters = Software::query()
            ->whereHas('versions', fn ($query) => $query->where('status', 'published'))
            ->orderBy('name')
            ->get(['id', 'name']);

        $requestedSoftwareId = $request->integer('software');

        $activeSoftwareId = $softwareFilters->contains('id', $requestedSoftwareId) ? $requestedSoftwareId : null;

        $entries = Version::query()
            ->with([
                'software',
                'textContents' => fn ($query) => $query->latest(),
            ])
            ->withCount(['vulnerabilities as open_vulnerabilities_count' => fn ($query) => $query->where('status', 'open')])
            ->where('status', 'published')
            ->when($activeSoftwareId, fn ($query) => $query->where('software_id', $activeSoftwareId))
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('version_number', 'like', "%{$search}%")
                        ->orWhereHas('software', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('textContents', fn ($query) => $query
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('content', 'like', "%{$search}%"));
                });
            })
            ->when($filters['date_from'] ?? null, fn ($query, string $date) => $query->whereDate('release_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($query, string $date) => $query->whereDate('release_date', '<=', $date))
            ->when($filters['support'] ?? null, fn ($query, string $support) => $query->where('support_status', $support))
            ->when(($filters['security'] ?? null) === 'attention', fn ($query) => $query->whereHas('vulnerabilities', fn ($query) => $query->where('status', 'open')))
            ->when(($filters['security'] ?? null) === 'clear', fn ($query) => $query->whereDoesntHave('vulnerabilities', fn ($query) => $query->where('status', 'open')))
            ->latest('release_date')
            ->limit(60)
            ->get()
            ->map(fn (Version $version) => [
                'id' => $version->id,
                'software_id' => $version->software_id,
                'software' => $version->software?->name,
                'version' => $version->version_number,
                'release_date' => $version->release_date?->toDateString(),
                'headline' => $version->textContents->first()?->title,
                'summary' => str($version->textContents->first()?->content)->limit(200)->toString(),
                'support_status' => $version->support_status?->value,
                'open_vulnerabilities' => $version->open_vulnerabilities_count,
            ]);

        return response()->json([
            'data' => $entries,
            'filters' => [
                'software' => $softwareFilters,
                'active' => [
                    ...$filters,
                    'software' => $activeSoftwareId,
                ],
            ],
        ]);
    }
}
