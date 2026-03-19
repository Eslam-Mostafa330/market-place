<?php

namespace App\Http\Requests\Customer\Auth;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'token'    => ['required', 'string', 'size:64'],
            'password' => ['required', 'string', 'max:100', 'confirmed', Password::defaults()],
        ];
    }
}