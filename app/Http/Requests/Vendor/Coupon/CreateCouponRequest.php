<?php

namespace App\Http\Requests\Vendor\Coupon;

use App\Enums\CouponType;
use App\Enums\DefineStatus;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class CreateCouponRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'                 => ['required', 'string', 'max:255'],
            'code'                 => ['required', 'string', 'max:255', 'unique:coupons,code'],
            'description'          => ['nullable', 'string', 'max:500'],
            'minimum_order'        => ['required', 'numeric', 'min:0'],
            'maximum_discount'     => ['nullable', 'numeric', 'min:0'],
            'coupon_type'          => ['required', 'integer', Rule::in(CouponType::values())],
            'value'                => ['required', 'numeric', 'min:0'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'starts_at'            => ['nullable', 'date', 'after_or_equal:today'],
            'expires_at'           => ['nullable', 'date', 'after:starts_at'],
            'status'               => ['sometimes', 'integer', Rule::in(DefineStatus::values())],
        ];
    }
}