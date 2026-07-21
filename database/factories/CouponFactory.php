<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Enums\DefineStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    public function definition(): array
    {
        return [
            'store_id'             => null,
            'code'                 => strtoupper(fake()->unique()->bothify('SAVE####')),
            'name'                 => 'Test coupon',
            'description'          => null,
            'minimum_order'        => 0,
            'maximum_discount'     => null,
            'coupon_type'          => CouponType::FIXED,
            'value'                => 10,
            'usage_limit_per_user' => null,
            'used_count'           => 0,
            'starts_at'            => null,
            'expires_at'           => null,
            'status'               => DefineStatus::ACTIVE,
        ];
    }

    /**
     * A percentage coupon, optionally capped at a maximum discount amount.
     */
    public function percentage(float $value, ?float $maximumDiscount = null): static
    {
        return $this->state(fn () => [
            'coupon_type'      => CouponType::PERCENTAGE,
            'value'            => $value,
            'maximum_discount' => $maximumDiscount,
        ]);
    }

    /**
     * A fixed-amount coupon.
     */
    public function fixed(float $value): static
    {
        return $this->state(fn () => [
            'coupon_type' => CouponType::FIXED,
            'value'       => $value,
        ]);
    }

    public function minimumOrder(float $amount): static
    {
        return $this->state(fn () => ['minimum_order' => $amount]);
    }
}
