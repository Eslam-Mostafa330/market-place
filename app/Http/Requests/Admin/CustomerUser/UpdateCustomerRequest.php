<?php

namespace App\Http\Requests\Admin\CustomerUser;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $customer = $this->route('customer');

        return [
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'max:255', Rule::unique('users', 'email')->ignore($customer), Rule::email()->strict()->preventSpoofing()],
            'phone'    => ['sometimes', 'nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:30', Rule::unique('users', 'phone')->ignore($customer)],
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