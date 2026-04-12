<?php

namespace App\Http\Requests\Admin\Settings;

use App\Http\Requests\FormRequest;

class UpdateSocialSettingsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'facebook'  => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'x'         => ['nullable', 'url', 'max:255'],
        ];
    }
}