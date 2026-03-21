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
        $name =  $this->faker->word() . ' branch ' . Str::random(4);

        return [
            'name'              => $name,
            'slug'              => Str::slug($name),
            'address'           => $this->faker->streetAddress(),
            'city'              => $this->faker->randomElement(['Cairo', 'Giza', 'New Cairo', 'Nasr City', 'Maadi']),
            'area'              => $this->faker->optional()->randomElement(['Mohandessin', 'Dokki', 'Rehab', 'Sheraton']),
            'phone'             => $this->faker->numerify('01#########'),
            'delivery_fee'      => $this->faker->randomFloat(2, 15, 50),
            'delivery_time_max' => $this->faker->numberBetween(30, 90),
            'latitude'          => $this->faker->randomFloat(8, 29.95, 30.15),
            'longitude'         => $this->faker->randomFloat(8, 31.20, 31.40),
            'status'            => fake()->randomElement(DefineStatus::values()),
        ];
    }
}