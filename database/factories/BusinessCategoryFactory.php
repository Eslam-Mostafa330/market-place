<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BusinessCategory>
 */
class BusinessCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name'        => ucwords($name),
            'slug'        => Str::slug($name) . '-' . Str::random(5),
            'description' => fake()->sentence(),
            'image'       => null,
        ];
    }
}
