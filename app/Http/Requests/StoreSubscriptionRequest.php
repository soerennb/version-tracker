<?php

namespace App\Http\Requests;

use App\Enums\SubscriptionEvent;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'software_id' => ['required', 'integer', 'exists:software,id'],
            'event' => ['required', Rule::enum(SubscriptionEvent::class)],
        ];
    }
}
