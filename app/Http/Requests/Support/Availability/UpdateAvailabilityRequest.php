<?php

namespace App\Http\Requests\Support\Availability;

use App\Enums\AgentAvailability;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAvailabilityRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'availability' => ['required', 'integer', Rule::in(AgentAvailability::values())],
        ];
    }
}