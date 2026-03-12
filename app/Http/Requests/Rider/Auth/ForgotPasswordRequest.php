<?php

namespace App\Http\Requests\Rider\Auth;

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
            'email' => ['required', 'email', Rule::exists('users', 'email')->where(fn ($query) => $query->where('role', UserRole::RIDER->value))],
        ];
    }
}