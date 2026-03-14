<?php

namespace App\Http\Requests\Vendor\Store;

use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_category_id' => ['sometimes', 'uuid', 'exists:business_categories,id'],
            'name'                 => ['sometimes', 'string', 'max:255', Rule::unique('stores', 'name')->ignore($this->store->id)],
            'description'          => ['nullable', 'string', 'max:1000'],
            'logo'                 => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:1024'],
            'image'                => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:1024'],
        ];
    }
}