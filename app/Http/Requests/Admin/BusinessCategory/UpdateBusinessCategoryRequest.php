<?php

namespace App\Http\Requests\Admin\BusinessCategory;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $businessCategory = $this->route('business_category');

        return [
            'name'        => ['required', 'string', 'max:255', Rule::unique('business_categories', 'name')->ignore($businessCategory)],
            'description' => ['nullable', 'string', 'max:1000'],
            'image'       => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:1024'],
        ];
    }
}