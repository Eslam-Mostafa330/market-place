<?php

namespace App\Http\Requests\Vendor\Product;

use App\Enums\BooleanStatus;
use App\Enums\DefineStatus;
use App\Http\Requests\FormRequest;
use App\Rules\ProductCategory\SelectableProductCategory;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'product_category_id' => ['sometimes', 'required', 'uuid', new SelectableProductCategory],
            'name'                => ['sometimes', 'required', 'string', 'max:255', Rule::unique('products', 'name')->where('store_id', $this->store->id)->ignore($this->product->id)],
            'description'         => ['nullable', 'string', 'max:1000'],
            'image'               => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:1024'],
            'price'               => ['sometimes', 'required', 'numeric', 'min:0'],
            'sale_price'          => ['nullable', 'numeric', 'min:0', 'lt:price'],
            'quantity'            => ['sometimes', 'required', 'integer', 'min:1', 'max:65535'],
            'preparation_time'    => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'is_featured'         => ['sometimes', 'integer', Rule::in(BooleanStatus::values())],
            'status'              => ['sometimes', 'integer', Rule::in(DefineStatus::values())],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_category_id' => 'product category',
        ];
    }
}