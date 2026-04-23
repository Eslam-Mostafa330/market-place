<?php

namespace App\Http\Requests\Rider\Dashboard;

use App\Http\Requests\FormRequest;

class ValidateFiltersRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year'  => ['nullable', 'integer', 'min:2000', 'max:' . now()->year],
            'month' => ['nullable', 'integer', 'min:1', 'max:12', 'required_with:year'],
        ];
    }
}