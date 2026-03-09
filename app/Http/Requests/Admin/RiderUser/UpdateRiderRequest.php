<?php

namespace App\Http\Requests\Admin\RiderUser;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateRiderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rider = $this->route('rider');

        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'max:255', Rule::unique('users', 'email')->ignore($rider), Rule::email()->strict()->preventSpoofing()],
            'phone'    => ['sometimes', 'nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:30', Rule::unique('users', 'phone')->ignore($rider)],
            'password' => ['sometimes', 'nullable', 'confirmed', 'max:100', Password::defaults()],
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