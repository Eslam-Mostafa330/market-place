<?php

namespace App\Http\Requests\Customer\Order;

use App\Enums\PaymentMethod;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'store_branch_id'    => ['required', 'uuid'],
            'address_id'         => ['required', 'uuid'],
            'items'              => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'coupon_code'        => ['nullable', 'string', 'max:255'],
            'payment_method'     => ['required', 'integer', Rule::in(PaymentMethod::values())],
            'use_wallet'         => ['nullable', 'boolean'],
            'notes'              => ['nullable', 'string', 'max:500'],
        ];
    }
}