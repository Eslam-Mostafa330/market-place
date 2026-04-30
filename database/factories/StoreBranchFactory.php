<?php

namespace Database\Factories;

use App\Enums\DefineStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StoreBranch>
 */
class StoreBranchFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Branch',
            'slug' => Str::slug('branch'),
            'address' => $this->faker->address(),
            'city' => 'Cairo',
            'area' => null,
            'phone' => $this->faker->numerify('01#########'),
            'delivery_fee' => $this->faker->randomFloat(2, 10, 50),
            'delivery_time_max' => $this->faker->numberBetween(20, 90),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'status' => fake()->randomElement(DefineStatus::values()),
        ];
    }
}