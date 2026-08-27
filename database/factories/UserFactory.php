<?php

namespace Database\Factories;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'phone'             => fake()->unique()->numerify('01#########'),
            'password'          => Hash::make('password'),
            'role'              => UserRole::CUSTOMER,
            'status'            => DefineStatus::ACTIVE,
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ];
    }

    public function customer(): static
    {
        return $this->state(fn () => ['role' => UserRole::CUSTOMER]);
    }

    public function vendor(): static
    {
        return $this->state(fn () => ['role' => UserRole::VENDOR]);
    }

    public function rider(): static
    {
        return $this->state(fn () => ['role' => UserRole::RIDER]);
    }

    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::ADMIN]);
    }

    public function support(): static
    {
        return $this->state(fn () => ['role' => UserRole::SUPPORT]);
    }
}
