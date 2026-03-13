<?php

namespace App\Http\Requests\Rider\Location;

use App\Enums\RiderAvailability;
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
            'rider_availability' => ['required', 'integer', Rule::in(RiderAvailability::values())],
        ];
    }
}