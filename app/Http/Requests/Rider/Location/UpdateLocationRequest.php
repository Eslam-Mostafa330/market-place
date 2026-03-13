<?php

namespace App\Http\Requests\Rider\Location;

use App\Http\Requests\FormRequest;

class UpdateLocationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'current_longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}