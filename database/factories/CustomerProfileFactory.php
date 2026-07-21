<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomerProfile>
 */
class CustomerProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'        => User::factory()->customer(),
            'date_of_birth'  => fake()->date(),
            'preferences'    => null,
            'wallet_balance' => 0,
            'loyalty_points' => 0,
        ];
    }

    public function withWallet(float $balance): static
    {
        return $this->state(fn () => ['wallet_balance' => $balance]);
    }

    public function withPoints(int $points): static
    {
        return $this->state(fn () => ['loyalty_points' => $points]);
    }
}
