<?php

namespace App\Http\Requests\Rider\Auth;

use App\Http\Requests\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:255'],
            'password'   => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'identifier' => __('auth.identifier_field'),
        ];
    }
}