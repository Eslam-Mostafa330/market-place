<?php

namespace Database\Factories;

use App\Enums\VendorVerificationStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorProfile>
 */
class VendorProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'              => User::factory()->vendor(),
            'business_name'        => fake()->company(),
            'business_license'     => fake()->bothify('LIC-####'),
            'business_description' => fake()->sentence(),
            'business_phone'       => fake()->numerify('01#########'),
            'business_email'       => fake()->unique()->safeEmail(),
            'verification_status'  => VendorVerificationStatus::VERIFIED,
        ];
    }
}
