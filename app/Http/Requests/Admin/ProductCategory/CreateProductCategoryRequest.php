<?php

namespace App\Http\Requests\Admin\ProductCategory;

use App\Http\Requests\FormRequest;
use App\Rules\ProductCategory\SingleCategoryLevel;

class CreateProductCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:255', 'unique:product_categories,name'],
            'parent_id' => ['nullable', 'string', 'max:36', 'exists:product_categories,id', new SingleCategoryLevel()],
        ];
    }
}