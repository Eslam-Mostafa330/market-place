<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserAddress>
 */
class UserAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'         => User::factory()->customer(),
            'country'         => 'Egypt',
            'city'            => 'Cairo',
            'state'           => 'Cairo',
            'postal_code'     => fake()->numerify('#####'),
            'address_line_1'  => fake()->streetAddress(),
            'address_line_2'  => null,
            'additional_info' => null,
            'contact_phone'   => fake()->numerify('01#########'),
            'latitude'        => fake()->latitude(),
            'longitude'       => fake()->longitude(),
        ];
    }
}
