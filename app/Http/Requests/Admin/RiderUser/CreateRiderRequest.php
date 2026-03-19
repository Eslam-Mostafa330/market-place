<?php

namespace App\Http\Requests\Admin\RiderUser;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateRiderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'min:2', 'max:255'],
            'email'          => ['required', 'max:255', 'unique:users,email', Rule::email()->strict()->preventSpoofing()],
            'phone'          => ['required', 'string', 'unique:users,phone', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:25'],
            'password'       => ['required', 'confirmed', 'max:100', Password::defaults()],
            'license_number' => ['required', 'string', 'max:100', 'unique:rider_profiles,license_number'],
            'license_expiry' => ['required', 'date', 'after_or_equal:today'],
            'vehicle_type'   => ['required', 'string', 'max:100'],
            'vehicle_number' => ['required', 'string', 'max:100', 'unique:rider_profiles,vehicle_number'],
        ];
    }
}