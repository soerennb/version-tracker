<?php

namespace App\Models;

use Database\Factories\ComponentVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class ComponentVersion extends Model
{
    /** @use HasFactory<ComponentVersionFactory> */
    use HasFactory;

    public const TED_STATUSES = ['unknown', 'accepted', 'not_accepted'];

    protected $attributes = ['ted_acceptance_status' => 'unknown'];

    protected $fillable = ['tracked_component_id', 'version_label', 'notes', 'ted_acceptance_status', 'ted_checked_at'];

    protected function casts(): array
    {
        return ['ted_checked_at' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $version): void {
            if ($version->exists && $version->isDirty(['tracked_component_id', 'version_label'])) {
                throw ValidationException::withMessages(['version_label' => 'Component version identifiers are immutable.']);
            }

            if ($version->component?->kind === 'eforms_sdk'
                && preg_match('/^[0-9]+\.[0-9]+(?:\.[0-9]+)?$/', (string) $version->version_label) !== 1) {
                throw ValidationException::withMessages(['version_label' => 'Use major.minor or major.minor.patch for an eForms SDK version.']);
            }

            if (! in_array($version->ted_acceptance_status, self::TED_STATUSES, true)
                || ($version->component?->kind !== 'eforms_sdk' && ($version->ted_acceptance_status !== 'unknown' || $version->ted_checked_at !== null))
                || ($version->ted_acceptance_status !== 'unknown' && $version->ted_checked_at === null)
                || ($version->ted_acceptance_status === 'unknown' && $version->ted_checked_at !== null)) {
                throw ValidationException::withMessages(['ted_acceptance_status' => 'Set a checked date for a confirmed eForms SDK acceptance status.']);
            }
        });
    }

    /** @return array{status:string,checked_at:?string} */
    public function tedAcceptance(): array
    {
        return [
            'status' => $this->ted_acceptance_status,
            'checked_at' => $this->ted_checked_at?->toDateString(),
        ];
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(TrackedComponent::class, 'tracked_component_id');
    }
}
