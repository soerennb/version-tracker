<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\ReviewAction;
use App\Enums\VersionStatus;
use App\Helpers\AuditHelper;
use App\Models\SbomDocument;
use App\Models\User;
use App\Models\Version;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class SbomIngestionService
{
    public function __construct(
        protected SbomParser $parser,
    ) {}

    /**
     * Ingest an SBOM and normalize its components/findings in one transaction.
     *
     * @return array{document:SbomDocument,created:bool}
     */
    public function ingest(
        Version $version,
        string $payload,
        ?string $filename = null,
        ?string $format = null,
        string $source = 'manual',
        ?string $idempotencyKey = null,
        ?User $user = null,
    ): array {
        $hash = hash('sha256', $payload);
        $existing = $this->existingDocument($version, $hash, $idempotencyKey);

        if ($existing) {
            return ['document' => $existing->load(['components.findings', 'findings']), 'created' => false];
        }

        try {
            $parsed = $this->parser->parse($payload, $format);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['document' => $exception->getMessage()]);
        }

        $maxComponents = max((int) app(RuntimeSettings::class)->security()->sbom_max_components, 1);
        if (count($parsed['components']) > $maxComponents) {
            throw ValidationException::withMessages([
                'document' => 'The SBOM contains more components than the configured limit of '.$maxComponents.'.',
            ]);
        }

        $filename = $this->normalizeFilename($filename, $parsed['format']);
        $idempotencyKey = filled($idempotencyKey) ? Str::limit(trim($idempotencyKey), 191, '') : null;
        $source = in_array($source, ['manual', 'ci', 'api'], true) ? $source : 'manual';

        try {
            $document = DB::transaction(function () use ($version, $payload, $parsed, $filename, $hash, $idempotencyKey, $source, $user): SbomDocument {
                $document = $version->sbomDocuments()->create([
                    'uploaded_by' => $user?->id,
                    'filename' => $filename,
                    'format' => $parsed['format'],
                    'spec_version' => $parsed['spec_version'],
                    'serial_number' => $parsed['serial_number'],
                    'document_hash' => $hash,
                    'idempotency_key' => $idempotencyKey,
                    'source' => $source,
                    'status' => 'processed',
                    'component_count' => count($parsed['components']),
                    'finding_count' => count($parsed['findings']),
                    'parsed_at' => now(),
                    'payload' => $payload,
                ]);

                $componentsByRef = [];
                foreach ($parsed['components'] as $componentData) {
                    $bomRef = (string) $componentData['bom_ref'];
                    if (isset($componentsByRef[$bomRef])) {
                        continue;
                    }

                    $component = $document->components()->create($componentData);
                    $componentsByRef[$bomRef] = $component;
                }

                $findingCount = 0;
                $findingIdentity = [];
                foreach ($parsed['findings'] as $findingData) {
                    $identity = ($findingData['bom_ref'] ?? '').'|'.$findingData['external_id'];
                    if (isset($findingIdentity[$identity])) {
                        continue;
                    }

                    $findingIdentity[$identity] = true;
                    $bomRef = $findingData['bom_ref'] ?? null;
                    unset($findingData['bom_ref']);
                    $findingData['sbom_component_id'] = $bomRef !== null && isset($componentsByRef[$bomRef])
                        ? $componentsByRef[$bomRef]->id
                        : null;
                    $findingData['first_seen_at'] = now();
                    $findingData['last_seen_at'] = now();
                    $document->findings()->create($findingData);
                    $findingCount++;
                }

                $document->forceFill([
                    'component_count' => count($componentsByRef),
                    'finding_count' => $findingCount,
                ])->save();

                AuditHelper::logAction($user, 'sbom_document.created', SbomDocument::class, (int) $document->id, [], [
                    'version_id' => $version->id,
                    'format' => $document->format,
                    'source' => $document->source,
                    'document_hash' => $document->document_hash,
                    'component_count' => $document->component_count,
                    'finding_count' => $document->finding_count,
                ]);

                return $document;
            });
        } catch (UniqueConstraintViolationException) {
            $existing = $this->existingDocument($version, $hash, $idempotencyKey);
            if (! $existing) {
                throw ValidationException::withMessages(['document' => 'The SBOM already exists.']);
            }

            return ['document' => $existing->load(['components.findings', 'findings']), 'created' => false];
        }

        $this->invalidateApprovalIfNeeded($version, $user);

        return ['document' => $document->load(['components.findings', 'findings']), 'created' => true];
    }

    private function existingDocument(Version $version, string $hash, ?string $idempotencyKey): ?SbomDocument
    {
        return SbomDocument::query()
            ->where('version_id', $version->id)
            ->where(function ($query) use ($hash, $idempotencyKey): void {
                $query->where('document_hash', $hash);

                if (filled($idempotencyKey)) {
                    $query->orWhere('idempotency_key', $idempotencyKey);
                }
            })
            ->latest('id')
            ->first();
    }

    private function normalizeFilename(?string $filename, string $format): string
    {
        $filename = trim((string) $filename);
        if ($filename === '') {
            return $format === 'spdx' ? 'sbom.spdx.json' : 'sbom.cyclonedx.json';
        }

        $basename = pathinfo($filename, PATHINFO_BASENAME);
        $safe = preg_replace('/[^A-Za-z0-9._ -]/', '-', $basename) ?: 'sbom.json';

        return Str::limit(trim($safe), 255, '');
    }

    private function invalidateApprovalIfNeeded(Version $version, ?User $user): void
    {
        $version->refresh();
        if ($version->status !== VersionStatus::DRAFT || $version->approval_status !== ApprovalStatus::APPROVED) {
            return;
        }

        $version->forceFill([
            'approval_status' => ApprovalStatus::PENDING,
            'rejection_reason' => null,
        ])->save();

        $version->reviews()->create([
            'user_id' => $user?->id,
            'action' => ReviewAction::COMMENT,
            'comment' => 'SBOM changed; approval requires a new review.',
            'metadata' => ['reason' => 'sbom_uploaded'],
        ]);
    }
}
