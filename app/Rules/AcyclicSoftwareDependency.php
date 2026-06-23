<?php

namespace App\Rules;

use App\Helpers\DependencyHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AcyclicSoftwareDependency implements ValidationRule
{
    public function __construct(
        protected readonly ?int $softwareId,
        protected readonly ?int $ignoreDependencyId = null,
        protected readonly bool $valueIsSource = false,
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value) || $this->softwareId === null) {
            return;
        }

        $sourceSoftwareId = $this->valueIsSource ? (int) $value : $this->softwareId;
        $dependsOnSoftwareId = $this->valueIsSource ? $this->softwareId : (int) $value;

        if (DependencyHelper::wouldCreateCycle($sourceSoftwareId, $dependsOnSoftwareId, $this->ignoreDependencyId)) {
            $fail(__('validation.custom.software_dependency.cyclic'));
        }
    }
}
