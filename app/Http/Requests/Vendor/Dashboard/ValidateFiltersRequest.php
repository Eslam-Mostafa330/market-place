<?php

namespace App\Http\Requests\Vendor\Dashboard;

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
        $currentYear = now()->year;

        return [
            'year'     => ['nullable', 'integer', 'between:2000,' . $currentYear],
            'month'    => ['nullable', 'integer', 'between:1,12', 'required_with:year'],
            'store_id' => ['nullable', 'uuid'],
        ];
    }
}