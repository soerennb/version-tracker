<?php

namespace App\Http\Controllers\Public;

use App\Enums\VersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicProductDetailResource;
use App\Http\Resources\PublicProductResource;
use App\Models\Software;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $software = Software::query()
            ->whereHas('versions', fn ($query) => $query->where('status', VersionStatus::PUBLISHED))
            ->with(['versions' => fn ($query) => $query
                ->where('status', VersionStatus::PUBLISHED)
                ->withCount(['vulnerabilities as open_vulnerabilities_count' => fn ($query) => $query->where('status', 'open')])
                ->latest('release_date')
                ->limit(1),
            ])
            ->orderBy('name')
            ->get();

        return PublicProductResource::collection($software);
    }

    public function show(Software $software): PublicProductDetailResource
    {
        abort_unless(
            $software->versions()->where('status', VersionStatus::PUBLISHED)->exists(),
            404,
        );

        $software->load(['versions' => fn ($query) => $query
            ->where('status', VersionStatus::PUBLISHED)
            ->with([
                'textContents' => fn ($query) => $query->latest(),
                'fileAttachments',
                'vulnerabilities' => fn ($query) => $query->whereNot('status', 'false_positive'),
            ])
            ->latest('release_date')
            ->limit(12),
        ]);

        return new PublicProductDetailResource($software);
    }
}
