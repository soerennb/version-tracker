<?php

namespace App\Http\Controllers\Api;

use App\Helpers\AuditHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReleaseExceptionRequest;
use App\Http\Requests\StoreSbomRequest;
use App\Http\Resources\ReleaseExceptionResource;
use App\Http\Resources\SbomDocumentResource;
use App\Models\ReleaseException;
use App\Models\SbomDocument;
use App\Models\Version;
use App\Services\ReleaseReadinessService;
use App\Services\SbomEnrichmentService;
use App\Services\SbomIngestionService;
use Illuminate\Http\JsonResponse;

class SbomController extends Controller
{
    public function __construct(
        protected SbomIngestionService $ingestion,
        protected ReleaseReadinessService $readiness,
        protected SbomEnrichmentService $enrichment,
    ) {}

    public function index(Version $version): JsonResponse
    {
        $this->authorize('viewSboms', $version);

        $documents = $version->sbomDocuments()
            ->withCount(['components', 'findings'])
            ->latest('id')
            ->paginate(25);

        return SbomDocumentResource::collection($documents)->response();
    }

    public function store(StoreSbomRequest $request, Version $version): JsonResponse
    {
        $this->authorize('uploadSbom', $version);

        $file = $request->file('file');
        $payload = $file?->get();
        if (! is_string($payload)) {
            $payload = $request->string('document')->toString();
        }

        $result = $this->ingestion->ingest(
            version: $version,
            payload: $payload,
            filename: $file?->getClientOriginalName(),
            format: $request->string('format')->toString() ?: null,
            source: $request->string('source')->toString() ?: 'manual',
            idempotencyKey: $request->string('idempotency_key')->toString() ?: null,
            user: $request->user(),
        );

        return SbomDocumentResource::make($result['document'])
            ->response()
            ->setStatusCode($result['created'] ? 201 : 200);
    }

    public function show(SbomDocument $sbomDocument): JsonResponse
    {
        $this->authorize('viewSboms', $sbomDocument->version);

        return SbomDocumentResource::make($sbomDocument->load(['components.findings', 'findings']))->response();
    }

    public function readiness(Version $version): JsonResponse
    {
        $this->authorize('viewReadiness', $version);

        return response()->json([
            'version_id' => $version->id,
            'readiness' => $this->readiness->evaluate($version),
        ]);
    }

    public function exceptions(Version $version): JsonResponse
    {
        $this->authorize('viewReadiness', $version);

        return ReleaseExceptionResource::collection(
            $version->releaseExceptions()->with('owner')->latest('id')->paginate(25),
        )->response();
    }

    public function storeException(StoreReleaseExceptionRequest $request, Version $version): JsonResponse
    {
        $this->authorize('manageExceptions', $version);

        $exception = $version->releaseExceptions()->create([
            ...$request->validated(),
            'owner_id' => $request->user()?->id,
            'approved_by' => $request->user()?->id,
            'approved_at' => now(),
        ])->load('owner');

        AuditHelper::logAction(
            $request->user(),
            'release_exception.created',
            ReleaseException::class,
            (int) $exception->id,
            [],
            $exception->makeHidden('version')->toArray(),
        );

        return ReleaseExceptionResource::make($exception)->response()->setStatusCode(201);
    }

    public function destroyException(ReleaseException $releaseException): JsonResponse
    {
        $this->authorize('delete', $releaseException);

        $before = $releaseException->toArray();
        $releaseException->forceFill(['revoked_at' => now()])->save();

        AuditHelper::logAction(
            auth()->user(),
            'release_exception.revoked',
            ReleaseException::class,
            (int) $releaseException->id,
            $before,
            $releaseException->toArray(),
        );

        return ReleaseExceptionResource::make($releaseException->load('owner'))->response();
    }

    public function enrich(SbomDocument $sbomDocument): JsonResponse
    {
        abort_unless(auth()->user()?->can('manage_feeds'), 403);

        $result = $this->enrichment->enrich($sbomDocument);

        return response()->json([
            'sbom_id' => $sbomDocument->id,
            ...$result,
        ], $result['error'] === null ? 200 : 502);
    }
}
