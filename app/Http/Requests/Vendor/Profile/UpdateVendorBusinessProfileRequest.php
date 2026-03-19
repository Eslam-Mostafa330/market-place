<?php

namespace App\Http\Requests\Vendor\Profile;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorBusinessProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_name'        => ['sometimes', 'required', 'string', 'max:255'],
            'business_email'       => ['nullable', 'max:255', Rule::email()->strict()->preventSpoofing()],
            'business_license'     => ['nullable', 'string', 'max:255'],
            'business_phone'       => ['nullable', 'string', 'max:25', 'regex:/^[0-9\s\-\+\(\)]+$/'],
            'business_description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}