<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id'     => Order::factory(),
            'product_id'   => Product::factory(),
            'product_name' => 'Product',
            'quantity'     => 1,
            'unit_price'   => 10,
            'subtotal'     => 10,
        ];
    }
}
