<?php

namespace Database\Factories;

use App\Enums\DefineStatus;
use App\Models\ProductCategory;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = 'Product ' . fake()->unique()->numerify('####');

        return [
            'store_id' => Store::factory(),
            'product_category_id' => ProductCategory::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
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

    /**
     * An active product with an exact price and stock level.
     */
    public function stocked(int $quantity, float $price): static
    {
        return $this->state(fn () => [
            'quantity'   => $quantity,
            'price'      => $price,
            'sale_price' => null,
            'status'     => DefineStatus::ACTIVE,
        ]);
    }
}