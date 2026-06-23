<?php

namespace App\Http\Requests;

use App\Enums\Language;
use App\Models\TextContent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTextContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var TextContent|null $textContent */
        $textContent = $this->route('text_content') ?? $this->route('textContent');

        return $textContent
            ? ($this->user()?->can('update', $textContent) ?? false)
            : false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'language' => ['sometimes', Rule::enum(Language::class)],
        ];
    }
}
