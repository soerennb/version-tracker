<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Helpers\AuditHelper;
use App\Models\ComponentVersion;
use App\Models\ReleaseComposition;
use App\Models\Version;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReleaseCompositionService
{
    public function isComplete(Version $version): bool
    {
        if (! $version->software?->tracks_release_composition) {
            return true;
        }

        $composition = $version->composition;

        return $composition !== null
            && $composition->baselineVersion?->component?->kind === 'baseline'
            && $composition->eformsComponentVersion?->component?->kind === 'eforms_component'
            && $composition->activeEformsSdkVersion?->component?->kind === 'eforms_sdk'
            && $composition->supportedInterfaces->contains('component_version_id', $composition->active_eforms_sdk_version_id);
    }

    /** @param array<string, mixed> $data */
    public function save(Version $version, array $data): ReleaseComposition
    {
        abort_unless($version->status?->isDraft(), 403);
        Gate::authorize('update', $version);

        if (! $version->software?->tracks_release_composition) {
            throw ValidationException::withMessages(['version' => 'Release composition is not enabled for this software.']);
        }

        $this->assertComponentKind((int) $data['baseline_version_id'], 'baseline');
        $this->assertComponentKind((int) $data['eforms_component_version_id'], 'eforms_component');
        $this->assertComponentKind((int) $data['active_eforms_sdk_version_id'], 'eforms_sdk');

        $supportedIds = array_values(array_unique(array_map('intval', $data['supported_interface_version_ids'])));
        if (! in_array((int) $data['active_eforms_sdk_version_id'], $supportedIds, true)) {
            throw ValidationException::withMessages(['active_eforms_sdk_version_id' => 'The active eForms SDK must be supported by this release.']);
        }

        foreach ($supportedIds as $supportedId) {
            $component = ComponentVersion::query()->with('component')->findOrFail($supportedId);
            if (! in_array($component->component?->kind, ['interface', 'eforms_sdk'], true) || $component->component->customer_id !== null) {
                throw ValidationException::withMessages(['supported_interface_version_ids' => 'Select global interface or eForms SDK versions.']);
            }
        }

        return DB::transaction(function () use ($version, $data, $supportedIds): ReleaseComposition {
            $previous = $version->composition?->load('supportedInterfaces')?->toArray() ?? [];
            $composition = ReleaseComposition::query()->updateOrCreate(
                ['version_id' => $version->id],
                [
                    'baseline_version_id' => $data['baseline_version_id'],
                    'eforms_component_version_id' => $data['eforms_component_version_id'],
                    'active_eforms_sdk_version_id' => $data['active_eforms_sdk_version_id'],
                ],
            );
            $composition->supportedInterfaces()->delete();
            foreach ($supportedIds as $supportedId) {
                $composition->supportedInterfaces()->create(['component_version_id' => $supportedId]);
            }

            if ($version->approval_status === ApprovalStatus::APPROVED) {
                app(VersionService::class)->update($version, []);
            }

            AuditHelper::logAction(auth()->user(), 'release_composition.saved', ReleaseComposition::class, (int) $composition->id, $previous, $composition->load('supportedInterfaces')->toArray());

            return $composition;
        });
    }

    private function assertComponentKind(int $id, string $kind): void
    {
        $version = ComponentVersion::query()->with('component')->findOrFail($id);
        if ($version->component?->kind !== $kind || $version->component->customer_id !== null) {
            throw ValidationException::withMessages([$kind.'_version_id' => 'Select a global component version of the correct type.']);
        }
    }
}
