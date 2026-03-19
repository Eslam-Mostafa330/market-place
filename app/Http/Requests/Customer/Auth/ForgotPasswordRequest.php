<?php

namespace App\Http\Requests\Customer\Auth;

use App\Enums\UserRole;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class ForgotPasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255', Rule::exists('users', 'email')->where(fn ($query) => $query->where('role', UserRole::CUSTOMER->value))],
        ];
    }
}