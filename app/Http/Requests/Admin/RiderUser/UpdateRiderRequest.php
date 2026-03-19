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
            'name'           => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'email'          => ['sometimes', 'required', 'max:255', Rule::unique('users', 'email')->ignore($rider), Rule::email()->strict()->preventSpoofing()],
            'phone'          => ['sometimes', 'required', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:25', Rule::unique('users', 'phone')->ignore($rider)],
            'password'       => ['sometimes', 'required', 'confirmed', 'max:100', Password::defaults()],
            'license_number' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('rider_profiles', 'license_number')->ignore($riderProfileId)],
            'license_expiry' => ['sometimes', 'required', 'date', 'after_or_equal:today'],
            'vehicle_type'   => ['sometimes', 'required', 'string', 'max:100'],
            'vehicle_number' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('rider_profiles', 'vehicle_number')->ignore($riderProfileId)],
        ];
    }
}