<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\FormRequest;
use App\Rules\Login\EmailOrPhone;

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
            'identifier' => ['required', 'string', 'max:255', new EmailOrPhone],
            'password'   => ['required', 'string', 'max:100'],
        ];
    }
}