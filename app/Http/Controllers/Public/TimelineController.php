<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Software;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function index(Request $request): JsonResponse
    {
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
            ->where('status', 'published')
            ->when($activeSoftwareId, fn ($query) => $query->where('software_id', $activeSoftwareId))
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
            ]);

        return response()->json([
            'data' => $entries,
            'filters' => [
                'software' => $softwareFilters,
                'active' => $activeSoftwareId,
            ],
        ]);
    }
}
