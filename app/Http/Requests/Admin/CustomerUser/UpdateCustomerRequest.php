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
            'name'     => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'email'    => ['sometimes', 'required', 'max:255', Rule::unique('users', 'email')->ignore($customer), Rule::email()->strict()->preventSpoofing()],
            'phone'    => ['sometimes', 'required', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:25', Rule::unique('users', 'phone')->ignore($customer)],
            'password' => ['sometimes', 'required', 'confirmed', 'max:100', Password::defaults()],
        ];
    }
}