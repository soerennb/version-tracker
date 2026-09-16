<?php

namespace App\Http\Requests;

use App\Models\Version;
use App\Services\RuntimeSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSbomRequest extends FormRequest
{
    public function authorize(): bool
    {
        $version = $this->route('version');

        return $version instanceof Version && ($this->user()?->can('uploadSbom', $version) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxKilobytes = max((int) app(RuntimeSettings::class)->security()->sbom_max_kb, 1);

        return [
            'file' => ['nullable', 'file', 'max:'.$maxKilobytes, 'mimes:json,spdx'],
            'document' => ['nullable', 'string', 'max:'.($maxKilobytes * 1024)],
            'format' => ['nullable', 'string', 'in:cyclonedx,spdx'],
            'source' => ['nullable', 'string', 'in:manual,ci,api'],
            'idempotency_key' => ['nullable', 'string', 'max:191'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('document') && ! $this->hasFile('file') && str_contains((string) $this->header('Content-Type'), 'application/json')) {
            $this->merge(['document' => $this->getContent()]);
        }

        if (! $this->filled('idempotency_key') && $this->header('Idempotency-Key')) {
            $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
        }
    }
}
