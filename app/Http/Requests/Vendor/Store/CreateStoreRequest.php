<?php

namespace App\Http\Requests\Vendor\Store;

use App\Http\Requests\FormRequest;

class CreateStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_category_id' => ['required', 'uuid', 'max:36', 'exists:business_categories,id'],
            'name'                 => ['required', 'string', 'max:255', 'unique:stores,name'],
            'description'          => ['nullable', 'string', 'max:1000'],
            'logo'                 => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:1024'],
            'image'                => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:1024'],
        ];
    }
}