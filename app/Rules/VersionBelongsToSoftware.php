<?php

namespace App\Rules;

use App\Models\Version;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class VersionBelongsToSoftware implements ValidationRule
{
    public function __construct(
        protected readonly ?int $softwareId,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        if ($this->softwareId === null) {
            $fail(__('validation.exists'));

            return;
        }

        $belongsToSoftware = Version::query()
            ->whereKey($value)
            ->where('software_id', $this->softwareId)
            ->exists();

        if (! $belongsToSoftware) {
            $fail(__('validation.exists'));
        }
    }
}
