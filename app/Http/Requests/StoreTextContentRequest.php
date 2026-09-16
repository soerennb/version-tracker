<?php

namespace App\Http\Requests;

use App\Enums\Language;
use App\Models\Version;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTextContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create_content') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Version|null $version */
        $version = $this->route('version');

        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'language' => [
                'required',
                Rule::enum(Language::class),
                Rule::unique('text_contents', 'language')
                    ->where(fn ($query) => $query->where('version_id', $version?->id)),
            ],
        ];
    }
}
