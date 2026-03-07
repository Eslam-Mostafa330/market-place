<?php

namespace App\Http\Requests\Admin\Profile;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateAdminProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'max:150', Rule::email()->strict()->preventSpoofing(), Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'nullable', 'confirmed', 'max:100', Password::defaults()],
            'phone'    => ['sometimes', 'nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:30', Rule::unique('users')->ignore($user->id)],
        ];
    }

    /**
     * Override the validated method to remove password, phone if it's empty
     *
     * @return array<string, mixed>
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $nullableOptional = ['password', 'phone'];

        return collect($data)
            ->reject(fn($value, $key) => in_array($key, $nullableOptional) && empty($value))
            ->toArray();
    }
}