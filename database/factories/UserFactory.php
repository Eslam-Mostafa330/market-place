<?php

namespace Database\Factories;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'phone'             => fake()->unique()->numerify('01#########'),
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
            'status'            => $this->faker->randomElement([DefineStatus::ACTIVE, DefineStatus::INACTIVE]),
            'role'              => UserRole::CUSTOMER,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::ADMIN,
        ]);
    }

    public function vendor(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::VENDOR,
        ]);
    }

    public function customer(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::CUSTOMER,
        ]);
    }

    public function rider(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::RIDER,
        ]);
    }
}
