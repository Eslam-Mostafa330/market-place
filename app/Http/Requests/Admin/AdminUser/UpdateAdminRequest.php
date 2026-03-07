<?php

namespace App\Http\Requests\Admin\AdminUser;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdminRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $admin = $this->route('admin');

        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'max:255', Rule::unique('users', 'email')->ignore($admin), Rule::email()->strict()->preventSpoofing()],
            'phone'    => ['required', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:30', Rule::unique('users', 'phone')->ignore($admin)],
            'password' => ['required', 'confirmed', 'max:100', Password::defaults()],
        ];
    }
}