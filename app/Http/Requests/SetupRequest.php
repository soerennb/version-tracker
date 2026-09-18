<?php

namespace App\Http\Requests;

use App\Services\RuntimeSettings;
use App\Services\SetupService;
use Illuminate\Foundation\Http\FormRequest;

class SetupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(SetupService::class)->isAvailable();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', app(RuntimeSettings::class)->passwordRule()],
            'application_name' => ['nullable', 'string', 'max:120'],
            'support_url' => ['nullable', 'url', 'max:255'],
            'default_locale' => ['required', 'in:de,en'],
            'fallback_locale' => ['required', 'in:de,en'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:120'],
            'github_sync_enabled' => ['sometimes', 'boolean'],
        ];
    }
}
