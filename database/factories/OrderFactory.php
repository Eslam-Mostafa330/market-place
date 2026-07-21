<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Store;
use App\Models\StoreBranch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $store = Store::factory();

        return [
            'customer_id'           => User::factory()->customer(),
            'store_id'              => $store,
            'store_branch_id'       => StoreBranch::factory()->for($store),
            'order_number'          => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(10)),
            'subtotal'              => 100,
            'delivery_fee'          => 10,
            'discount'              => 0,
            'wallet_discount'       => 0,
            'total'                 => 110,
            'payment_method'        => PaymentMethod::CASH,
            'order_status'          => OrderStatus::PENDING,
            'payment_status'        => PaymentStatus::PENDING,
            'commission_rate'       => 10,
            'commission_amount'     => 10,
            'vendor_earnings'       => 90,
            'rider_earnings'        => 10,
            'delivery_address_line' => fake()->streetAddress(),
            'delivery_city'         => 'Cairo',
            'delivery_state'        => 'Cairo',
            'delivery_country'      => 'Egypt',
            'delivery_phone'        => fake()->numerify('01#########'),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => ['order_status' => $status]);
    }

    public function paidByCard(): static
    {
        return $this->state(fn () => [
            'payment_method'    => PaymentMethod::VISA,
            'payment_status'    => PaymentStatus::PAID,
            'payment_intent_id' => 'pi_' . Str::random(16),
        ]);
    }

    public function withWalletDiscount(float $amount): static
    {
        return $this->state(fn () => ['wallet_discount' => $amount]);
    }
}
