<?php

namespace App\Http\Requests\Admin\Auth;

use App\Http\Requests\FormRequest;

class ResendOtpRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'temp_token' => ['required', 'string', 'exists:two_factor_codes,temp_token'],
        ];
    }
}