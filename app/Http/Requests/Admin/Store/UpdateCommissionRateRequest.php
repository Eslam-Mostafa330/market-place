<?php

namespace App\Http\Requests\Admin\Store;

use App\Http\Requests\FormRequest;

class UpdateCommissionRateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'commission_rate' => ['sometimes', 'required', 'numeric', 'min:0', 'max:99.99', 'decimal:0,2'],
        ];
    }
}