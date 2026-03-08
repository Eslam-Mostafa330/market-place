<?php

namespace App\Http\Requests\Admin\VendorUser;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateVendorRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'max:255', 'unique:users,email', Rule::email()->strict()->preventSpoofing()],
            'phone'    => ['required', 'string', 'unique:users,phone', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:30'],
            'password' => ['required', 'confirmed', 'max:100', Password::defaults()],
        ];
    }
}