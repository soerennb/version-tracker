<?php

namespace App\Models;

use Database\Factories\TrackedComponentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class TrackedComponent extends Model
{
    /** @use HasFactory<TrackedComponentFactory> */
    use HasFactory;

    public const KINDS = ['baseline', 'eforms_component', 'eforms_sdk', 'interface', 'customization'];

    protected $fillable = ['name', 'kind', 'customer_id'];

    protected static function booted(): void
    {
        static::saving(function (self $component): void {
            if (($component->kind === 'customization') !== ($component->customer_id !== null)) {
                throw ValidationException::withMessages(['customer_id' => 'Only customization components belong to a customer.']);
            }

            if ($component->exists && $component->isDirty(['kind', 'customer_id']) && $component->versions()->exists()) {
                throw ValidationException::withMessages(['kind' => 'A component with versions cannot change type or customer.']);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ComponentVersion::class);
    }
}
