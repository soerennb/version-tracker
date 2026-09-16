<?php

namespace App\Http\Controllers\Public;

use App\Enums\VersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicProductRequest;
use App\Http\Resources\PublicProductDetailResource;
use App\Http\Resources\PublicProductResource;
use App\Models\Software;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function index(PublicProductRequest $request): AnonymousResourceCollection
    {
        $software = Software::query()
            ->whereHas('versions', fn ($query) => $query->where('status', VersionStatus::PUBLISHED))
            ->when($request->string('q')->toString(), function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->string('support')->toString(), fn ($query, string $support) => $query->whereHas(
                'versions',
                fn ($query) => $query->where('status', VersionStatus::PUBLISHED)->where('support_status', $support),
            ))
            ->when($request->string('security')->toString() === 'attention', fn ($query) => $query->whereHas(
                'versions',
                fn ($query) => $query->where('status', VersionStatus::PUBLISHED)->whereHas(
                    'vulnerabilities',
                    fn ($query) => $query->where('status', 'open')->whereNot('status', 'false_positive'),
                ),
            ))
            ->when($request->string('security')->toString() === 'clear', fn ($query) => $query->whereDoesntHave(
                'versions',
                fn ($query) => $query->where('status', VersionStatus::PUBLISHED)->whereHas(
                    'vulnerabilities',
                    fn ($query) => $query->where('status', 'open')->whereNot('status', 'false_positive'),
                ),
            ))
            ->with(['versions' => fn ($query) => $query
                ->where('status', VersionStatus::PUBLISHED)
                ->with('textContents')
                ->withCount(['vulnerabilities as open_vulnerabilities_count' => fn ($query) => $query
                    ->where('status', 'open')
                    ->whereNot('status', 'false_positive')])
                ->latest('release_date')
                ->limit(1),
            ])
            ->withCount(['versions as published_versions_count' => fn ($query) => $query->where('status', VersionStatus::PUBLISHED)])
            ->orderBy('name')
            ->paginate(min(max($request->integer('per_page', 12), 1), 60))
            ->withQueryString();

        return PublicProductResource::collection($software);
    }

    public function show(PublicProductRequest $request, Software $software): PublicProductDetailResource
    {
        abort_unless(
            $software->versions()->where('status', VersionStatus::PUBLISHED)->exists(),
            404,
        );

        $software->load([
            'versions' => fn ($query) => $query
                ->where('status', VersionStatus::PUBLISHED)
                ->with([
                    'textContents' => fn ($query) => $query->latest(),
                    'fileAttachments',
                    'vulnerabilities' => fn ($query) => $query->whereNot('status', 'false_positive'),
                ])
                ->latest('release_date'),
            'dependenciesOutgoing' => fn ($query) => $query
                ->whereHas('dependsOnSoftware.versions', fn ($query) => $query->where('status', VersionStatus::PUBLISHED))
                ->where(function ($query): void {
                    $query->whereNull('applies_to_version_id')
                        ->orWhereHas('appliesToVersion', fn ($query) => $query->where('status', VersionStatus::PUBLISHED));
                }),
            'dependenciesOutgoing.dependsOnSoftware:id,name,status',
            'dependenciesOutgoing.appliesToVersion:id,version_number,status',
            'dependenciesOutgoing.minVersion' => fn ($query) => $query
                ->where('status', VersionStatus::PUBLISHED)
                ->select(['id', 'version_number', 'status']),
            'dependenciesOutgoing.maxVersion' => fn ($query) => $query
                ->where('status', VersionStatus::PUBLISHED)
                ->select(['id', 'version_number', 'status']),
        ]);

        return new PublicProductDetailResource($software);
    }
}
