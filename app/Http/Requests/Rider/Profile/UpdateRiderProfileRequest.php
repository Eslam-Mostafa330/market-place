<?php

namespace App\Http\Requests\Rider\Profile;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateRiderProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'name'     => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'email'    => ['sometimes', 'required', 'max:255', Rule::email()->strict()->preventSpoofing(), Rule::unique('users')->ignore($user->id)],
            'password' => ['sometimes', 'required', 'confirmed', 'max:100', Password::defaults()],
            'phone'    => ['sometimes', 'required', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:25', Rule::unique('users')->ignore($user->id)],
        ];
    }
}