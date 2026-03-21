<?php

namespace Database\Factories;

use App\Enums\BooleanStatus;
use App\Enums\DefineStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = implode(' ', $this->faker->words(2)) . ' ' . $this->faker->numberBetween(1000, 9999);
        $price = $this->faker->randomFloat(2, 15, 1200);
        $description = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry.';
        $salePrice = null;

        if ($this->faker->boolean(30)) {
            $salePrice = round($this->faker->randomFloat(2, $price * 0.5, $price * 0.9), 2);
        }

        return [
            'name'             => $name,
            'slug'             => Str::slug($name),
            'image'            => 'products/images/' . Str::slug($name) . '-' . $this->faker->numberBetween(1000, 9999) . '.jpg',
            'description'      => $description,
            'price'            => $price,
            'sale_price'       => $salePrice,
            'quantity'         => $this->faker->numberBetween(5, 500),
            'preparation_time' => $this->faker->boolean(40) ? $this->faker->numberBetween(5, 45) : 0,
            'is_featured'      => $this->faker->randomElement(BooleanStatus::values()),
            'status'           => $this->faker->randomElement(DefineStatus::values()),
        ];
    }
}