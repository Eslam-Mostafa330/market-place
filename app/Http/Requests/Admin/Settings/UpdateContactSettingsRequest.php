<?php

namespace App\Http\Requests\Admin\Settings;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email'    => ['nullable', 'max:255', Rule::email()->strict()->preventSpoofing()],
            'phone'    => ['nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:25'],
            'whatsapp' => ['nullable', 'string', 'regex:/^[0-9\s\-\+\(\)]+$/', 'max:25'],
        ];
    }
}