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
        $riderProfileId = $rider->riderProfile?->id;

        return [
            'name'           => ['required', 'string', 'max:100'],
            'email'          => ['required', 'max:255', Rule::unique('users', 'email')->ignore($rider), Rule::email()->strict()->preventSpoofing()],
            'phone'          => ['sometimes', 'nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:30', Rule::unique('users', 'phone')->ignore($rider)],
            'password'       => ['sometimes', 'nullable', 'confirmed', 'max:100', Password::defaults()],
            'license_number' => ['required', 'string', 'max:100', Rule::unique('rider_profiles', 'license_number')->ignore($riderProfileId)],
            'license_expiry' => ['required', 'date', 'after_or_equal:today'],
            'vehicle_type'   => ['required', 'string', 'max:100'],
            'vehicle_number' => ['required', 'string', 'max:100', Rule::unique('rider_profiles', 'vehicle_number')->ignore($riderProfileId)],
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