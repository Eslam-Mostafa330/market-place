<?php

namespace App\Http\Requests\Admin\Settings;

use App\Http\Requests\FormRequest;

class UpdateLoyaltyPointSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'loyalty_points' => ['required', 'integer', 'min:0'],
        ];
    }
}