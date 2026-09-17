<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\DeploymentEventType;
use App\Enums\DeploymentStatus;
use App\Enums\VersionStatus;
use App\Helpers\AuditHelper;
use App\Models\Deployment;
use App\Models\DeploymentEvent;
use App\Models\Environment;
use App\Models\User;
use App\Models\Version;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class DeploymentService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{deployment: Deployment, created: bool}
     */
    public function create(array $data, ?User $actor = null): array
    {
        $actor ??= $this->authenticatedUser();

        return DB::transaction(function () use ($data, $actor): array {
            $existing = $this->existingExternalDeployment($data['external_reference'] ?? null);

            if ($existing) {
                $this->assertSameExternalRequest($existing, $data);

                return [
                    'deployment' => $this->loadForResponse($existing),
                    'created' => false,
                ];
            }

            $softwareId = (int) $data['software_id'];
            $version = Version::query()->findOrFail((int) $data['version_id']);
            $environment = Environment::query()->findOrFail((int) $data['environment_id']);

            $this->assertVersionBelongsToSoftware($version, $softwareId);
            $this->assertVersionEligible($version, $environment);
            $this->assertNoActiveDeployment($softwareId, $environment->id);

            $deployment = Deployment::query()->create([
                'software_id' => $softwareId,
                'version_id' => $version->id,
                'environment_id' => $environment->id,
                'status' => DeploymentStatus::PLANNED,
                'scheduled_at' => $data['scheduled_at'] ?? null,
                'created_by' => $actor?->id,
                'change_reference' => $data['change_reference'] ?? null,
                'maintenance_window_start' => $data['maintenance_window_start'] ?? null,
                'maintenance_window_end' => $data['maintenance_window_end'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
                'source' => $this->source(),
                'notes' => $data['notes'] ?? null,
                'relation_type' => $data['relation_type'] ?? null,
                'related_deployment_id' => $data['related_deployment_id'] ?? null,
            ]);

            $this->recordEvent(
                $deployment,
                DeploymentEventType::CREATED,
                null,
                DeploymentStatus::PLANNED,
                $actor,
                null,
                ['source' => $deployment->source, 'relation_type' => $deployment->relation_type],
            );

            AuditHelper::logAction(
                $actor,
                'deployment.created',
                Deployment::class,
                (int) $deployment->id,
                [],
                $deployment->toArray(),
            );

            return [
                'deployment' => $this->loadForResponse($deployment),
                'created' => true,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Deployment $deployment, array $data, ?User $actor = null): Deployment
    {
        $actor ??= $this->authenticatedUser();

        return DB::transaction(function () use ($deployment, $data, $actor): Deployment {
            $locked = $this->lock($deployment);

            if ($locked->status !== DeploymentStatus::PLANNED) {
                throw ValidationException::withMessages([
                    'deployment' => __('deployments.errors.only_planned_editable'),
                ]);
            }

            $before = $locked->toArray();
            $locked->forceFill(Arr::only($data, [
                'scheduled_at',
                'change_reference',
                'maintenance_window_start',
                'maintenance_window_end',
                'notes',
            ]))->save();
            $after = $locked->fresh()->toArray();

            if ($before !== $after) {
                $this->recordEvent(
                    $locked,
                    DeploymentEventType::UPDATED,
                    DeploymentStatus::PLANNED,
                    DeploymentStatus::PLANNED,
                    $actor,
                    null,
                    ['old_values' => $before, 'new_values' => $after],
                );
                AuditHelper::logAction($actor, 'deployment.updated', Deployment::class, (int) $locked->id, $before, $after);
            }

            return $this->loadForResponse($locked);
        });
    }

    public function approve(Deployment $deployment, ?string $comment = null, ?User $actor = null): Deployment
    {
        $actor ??= $this->authenticatedUser();

        return $this->transition(
            $deployment,
            [DeploymentStatus::PLANNED],
            DeploymentStatus::APPROVED,
            DeploymentEventType::APPROVED,
            ['approved_at' => now(), 'approved_by' => $actor?->id],
            $comment,
            $actor,
            true,
        );
    }

    public function start(Deployment $deployment, ?string $comment = null, ?User $actor = null): Deployment
    {
        $actor ??= $this->authenticatedUser();

        return $this->transition(
            $deployment,
            [DeploymentStatus::APPROVED],
            DeploymentStatus::IN_PROGRESS,
            DeploymentEventType::STARTED,
            ['started_at' => now(), 'executed_by' => $actor?->id],
            $comment,
            $actor,
            true,
        );
    }

    public function succeed(Deployment $deployment, string $result, ?User $actor = null): Deployment
    {
        $actor ??= $this->authenticatedUser();

        return $this->complete($deployment, DeploymentStatus::SUCCEEDED, DeploymentEventType::SUCCEEDED, $result, $actor);
    }

    public function fail(Deployment $deployment, string $result, ?User $actor = null): Deployment
    {
        $actor ??= $this->authenticatedUser();

        return $this->complete($deployment, DeploymentStatus::FAILED, DeploymentEventType::FAILED, $result, $actor);
    }

    public function cancel(Deployment $deployment, string $reason, ?User $actor = null): Deployment
    {
        $actor ??= $this->authenticatedUser();

        if (blank($reason)) {
            throw ValidationException::withMessages([
                'comment' => __('deployments.errors.reason_required'),
            ]);
        }

        return $this->transition(
            $deployment,
            [DeploymentStatus::PLANNED, DeploymentStatus::APPROVED, DeploymentStatus::IN_PROGRESS],
            DeploymentStatus::CANCELED,
            DeploymentEventType::CANCELED,
            ['completed_at' => now(), 'result' => $reason],
            $reason,
            $actor,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function rollback(Deployment $deployment, array $data, ?User $actor = null): Deployment
    {
        $actor ??= $this->authenticatedUser();

        return DB::transaction(function () use ($deployment, $data, $actor): Deployment {
            $locked = $this->lock($deployment);

            if ($locked->status !== DeploymentStatus::SUCCEEDED) {
                throw ValidationException::withMessages([
                    'deployment' => __('deployments.errors.rollback_requires_success'),
                ]);
            }

            $rollbackVersion = Version::query()->findOrFail((int) $data['rollback_version_id']);
            $environment = Environment::query()->findOrFail($locked->environment_id);

            $this->assertVersionBelongsToSoftware($rollbackVersion, (int) $locked->software_id);
            $this->assertVersionEligible($rollbackVersion, $environment);

            $result = $this->create([
                'software_id' => $locked->software_id,
                'version_id' => $rollbackVersion->id,
                'environment_id' => $locked->environment_id,
                'scheduled_at' => $data['scheduled_at'] ?? now(),
                'change_reference' => $data['change_reference'] ?? null,
                'maintenance_window_start' => $data['maintenance_window_start'] ?? null,
                'maintenance_window_end' => $data['maintenance_window_end'] ?? null,
                'external_reference' => $data['external_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'relation_type' => 'rollback',
                'related_deployment_id' => $locked->id,
            ], $actor);

            return $result['deployment'];
        });
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    public function correct(Deployment $deployment, array $changes, string $reason, ?User $actor = null): Deployment
    {
        $actor ??= $this->authenticatedUser();

        return DB::transaction(function () use ($deployment, $changes, $reason, $actor): Deployment {
            $locked = $this->lock($deployment);

            if (! $locked->isTerminal()) {
                throw ValidationException::withMessages([
                    'deployment' => __('deployments.errors.corrections_require_terminal'),
                ]);
            }

            $allowedFields = [
                'scheduled_at',
                'approved_at',
                'started_at',
                'completed_at',
                'change_reference',
                'maintenance_window_start',
                'maintenance_window_end',
                'notes',
                'result',
            ];
            $changes = Arr::only($changes, $allowedFields);

            if ($changes === []) {
                throw ValidationException::withMessages([
                    'changes' => __('deployments.errors.correction_fields_required'),
                ]);
            }

            if (blank($reason)) {
                throw ValidationException::withMessages([
                    'reason' => __('deployments.errors.reason_required'),
                ]);
            }

            $before = Arr::only($locked->toArray(), array_keys($changes));

            $this->recordEvent(
                $locked,
                DeploymentEventType::CORRECTED,
                $locked->status,
                $locked->status,
                $actor,
                $reason,
                ['old_values' => $before, 'new_values' => $changes],
            );
            AuditHelper::logAction($actor, 'deployment.corrected', Deployment::class, (int) $locked->id, $before, $changes);

            return $this->loadForResponse($locked);
        });
    }

    /**
     * @param  array<int, DeploymentStatus>  $expectedStatuses
     * @param  array<string, mixed>  $attributes
     */
    protected function transition(
        Deployment $deployment,
        array $expectedStatuses,
        DeploymentStatus $to,
        DeploymentEventType $eventType,
        array $attributes,
        ?string $comment,
        ?User $actor,
        bool $validateVersionEligibility = false,
    ): Deployment {
        return DB::transaction(function () use ($deployment, $expectedStatuses, $to, $eventType, $attributes, $comment, $actor, $validateVersionEligibility): Deployment {
            $locked = $this->lock($deployment);
            $this->assertStatus($locked, $expectedStatuses);

            if ($validateVersionEligibility) {
                $this->assertVersionEligible(
                    Version::query()->findOrFail($locked->version_id),
                    Environment::query()->findOrFail($locked->environment_id),
                );
            }

            $from = $locked->status;
            $before = $locked->toArray();

            $locked->forceFill([
                ...$attributes,
                'status' => $to,
            ])->save();
            $after = $locked->fresh()->toArray();

            $this->recordEvent($locked, $eventType, $from, $to, $actor, $comment);
            AuditHelper::logAction($actor, 'deployment.'.$eventType->value, Deployment::class, (int) $locked->id, $before, $after);

            return $this->loadForResponse($locked);
        });
    }

    protected function complete(
        Deployment $deployment,
        DeploymentStatus $status,
        DeploymentEventType $eventType,
        string $result,
        ?User $actor,
    ): Deployment {
        if (blank($result)) {
            throw ValidationException::withMessages([
                'result' => __('deployments.errors.result_required'),
            ]);
        }

        return DB::transaction(function () use ($deployment, $status, $eventType, $result, $actor): Deployment {
            $locked = $this->lock($deployment);
            $this->assertStatus($locked, [DeploymentStatus::IN_PROGRESS]);

            $parent = null;
            if ($status === DeploymentStatus::SUCCEEDED && $locked->relation_type === 'rollback') {
                $parent = Deployment::query()->lockForUpdate()->findOrFail($locked->related_deployment_id);

                if ($parent->status !== DeploymentStatus::SUCCEEDED) {
                    throw ValidationException::withMessages([
                        'deployment' => __('deployments.errors.rollback_parent_not_successful'),
                    ]);
                }
            }

            $from = $locked->status;
            $before = $locked->toArray();
            $locked->forceFill([
                'status' => $status,
                'completed_at' => now(),
                'result' => $result,
            ])->save();
            $after = $locked->fresh()->toArray();

            $this->recordEvent($locked, $eventType, $from, $status, $actor, $result);
            AuditHelper::logAction($actor, 'deployment.'.$eventType->value, Deployment::class, (int) $locked->id, $before, $after);

            if ($parent) {
                $parentBefore = $parent->toArray();
                $parent->forceFill(['status' => DeploymentStatus::ROLLED_BACK])->save();
                $parentAfter = $parent->fresh()->toArray();

                $this->recordEvent(
                    $parent,
                    DeploymentEventType::ROLLED_BACK,
                    DeploymentStatus::SUCCEEDED,
                    DeploymentStatus::ROLLED_BACK,
                    $actor,
                    $result,
                    ['rollback_deployment_id' => $locked->id],
                );
                AuditHelper::logAction($actor, 'deployment.rolled_back', Deployment::class, (int) $parent->id, $parentBefore, $parentAfter);
            }

            return $this->loadForResponse($locked);
        });
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function recordEvent(
        Deployment $deployment,
        DeploymentEventType $type,
        ?DeploymentStatus $from,
        ?DeploymentStatus $to,
        ?User $actor,
        ?string $comment = null,
        array $metadata = [],
    ): DeploymentEvent {
        $event = DeploymentEvent::query()->create([
            'deployment_id' => $deployment->id,
            'actor_id' => $actor?->id,
            'type' => $type,
            'from_status' => $from?->value,
            'to_status' => $to?->value,
            'comment' => $comment,
            'metadata' => $metadata === [] ? null : $metadata,
            'interface' => $this->interface(),
            'api_token_id' => $this->apiTokenId($actor),
        ]);

        AuditHelper::logAction(
            $actor,
            'deployment_event.created',
            DeploymentEvent::class,
            (int) $event->id,
            [],
            $event->toArray(),
        );

        return $event;
    }

    /**
     * @param  array<int, DeploymentStatus>  $expectedStatuses
     */
    protected function assertStatus(Deployment $deployment, array $expectedStatuses): void
    {
        if (! in_array($deployment->status, $expectedStatuses, true)) {
            throw ValidationException::withMessages([
                'deployment' => __('deployments.errors.invalid_transition', [
                    'status' => $deployment->status?->label() ?? $deployment->status?->value,
                ]),
            ]);
        }
    }

    protected function assertVersionBelongsToSoftware(Version $version, int $softwareId): void
    {
        if ((int) $version->software_id !== $softwareId) {
            throw ValidationException::withMessages([
                'version_id' => __('deployments.errors.version_software_mismatch'),
            ]);
        }
    }

    protected function assertVersionEligible(Version $version, Environment $environment): void
    {
        if ($version->approval_status !== ApprovalStatus::APPROVED) {
            throw ValidationException::withMessages([
                'version_id' => __('deployments.errors.version_not_approved'),
            ]);
        }

        if ($environment->is_production && $version->status !== VersionStatus::PUBLISHED) {
            throw ValidationException::withMessages([
                'version_id' => __('deployments.errors.production_requires_published'),
            ]);
        }

        if (! $environment->is_active) {
            throw ValidationException::withMessages([
                'environment_id' => __('deployments.errors.environment_inactive'),
            ]);
        }
    }

    protected function assertNoActiveDeployment(int $softwareId, int $environmentId): void
    {
        $active = Deployment::query()
            ->where('software_id', $softwareId)
            ->where('environment_id', $environmentId)
            ->whereIn('status', array_map(
                static fn (DeploymentStatus $status): string => $status->value,
                [DeploymentStatus::PLANNED, DeploymentStatus::APPROVED, DeploymentStatus::IN_PROGRESS],
            ))
            ->exists();

        if ($active) {
            throw ValidationException::withMessages([
                'environment_id' => __('deployments.errors.active_deployment_exists'),
            ]);
        }
    }

    protected function existingExternalDeployment(mixed $externalReference): ?Deployment
    {
        if (blank($externalReference)) {
            return null;
        }

        return Deployment::query()->where('external_reference', $externalReference)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function assertSameExternalRequest(Deployment $existing, array $data): void
    {
        $matches = (int) $existing->software_id === (int) $data['software_id']
            && (int) $existing->version_id === (int) $data['version_id']
            && (int) $existing->environment_id === (int) $data['environment_id'];

        if (! $matches) {
            throw ValidationException::withMessages([
                'external_reference' => __('deployments.errors.external_reference_conflict'),
            ]);
        }
    }

    protected function lock(Deployment $deployment): Deployment
    {
        return Deployment::query()->lockForUpdate()->findOrFail($deployment->id);
    }

    protected function loadForResponse(Deployment $deployment): Deployment
    {
        return Deployment::query()
            ->with([
                'software',
                'version',
                'environment',
                'creator',
                'approver',
                'executor',
                'relatedDeployment.version',
                'events.actor',
            ])
            ->findOrFail($deployment->id);
    }

    protected function authenticatedUser(): ?User
    {
        return auth()->user() instanceof User ? auth()->user() : null;
    }

    protected function source(): string
    {
        return request()->bearerToken() ? 'api' : 'web';
    }

    protected function interface(): string
    {
        return request()->is('mcp/*') ? 'mcp' : (request()->is('api/*') ? 'rest' : 'web');
    }

    protected function apiTokenId(?User $actor): ?int
    {
        $token = $actor?->currentAccessToken();

        return $token instanceof PersonalAccessToken ? (int) $token->id : null;
    }
}
