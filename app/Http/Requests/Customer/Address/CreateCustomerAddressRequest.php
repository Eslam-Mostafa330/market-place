<?php

namespace App\Http\Requests\Customer\Address;

use App\Enums\AddressType;
use App\Enums\BooleanStatus;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class CreateCustomerAddressRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'country'          => ['required', 'string', 'min:3', 'max:255'],
            'city'             => ['required', 'string', 'min:3', 'max:255'],
            'state'            => ['required', 'string', 'min:3', 'max:255'],
            'postal_code'      => ['nullable', 'string', 'min:2', 'max:20'],
            'address_line_1'   => ['required', 'string', 'min:5', 'max:255'],
            'address_line_2'   => ['nullable', 'string', 'min:5', 'max:255'],
            'contact_phone'    => ['nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:25'],
            'additional_phone' => ['nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:25'],
            'additional_info'  => ['nullable', 'string', 'max:500'],
            'latitude'         => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'        => ['nullable', 'numeric', 'between:-180,180'],
            'is_default'       => ['sometimes', 'integer', Rule::in(BooleanStatus::values())],
            'type'             => ['required', 'integer', Rule::in(AddressType::values())],
        ];
    }
}