<?php

namespace App\Http\Requests\Vendor\Auth;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'min:2', 'max:255'],
            'email'    => ['required', 'max:255', 'unique:users,email', Rule::email()->strict()->preventSpoofing()],
            'phone'    => ['required', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:25', 'unique:users,phone'],
            'password' => ['required', 'confirmed', 'max:100', Password::defaults()],
        ];
    }
}