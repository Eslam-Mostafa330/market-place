<?php

namespace Database\Factories;

use App\Enums\DefineStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Product',
            'slug' => Str::slug('product'),
            'image' => 'products/images/default.jpg',
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10, 1000),
            'sale_price' => null,
            'quantity' => fake()->numberBetween(5, 500),
            'preparation_time' => 0,
            'is_featured' => fake()->boolean(),
            'status' => fake()->randomElement(DefineStatus::values()),
        ];
    }
}