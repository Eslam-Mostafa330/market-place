<?php

namespace App\Http\Requests\Admin\ProductCategory;

use App\Http\Requests\FormRequest;
use App\Rules\ProductCategory\SingleCategoryLevel;
use Illuminate\Validation\Rule;

class UpdateProductCategoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productCategory = $this->route('product_category');

        return [
            'name'      => ['sometimes', 'required', 'string', 'max:255', Rule::unique('product_categories', 'name')->ignore($productCategory)],
            'parent_id' => ['nullable', 'string', 'max:36', 'exists:product_categories,id', new SingleCategoryLevel(),],
        ];
    }
}