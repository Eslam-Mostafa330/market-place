<?php

namespace Database\Factories;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupportMessage>
 */
class SupportMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => SupportTicket::factory(),
            'sender_id' => User::factory()->customer(),
            'body'      => fake()->sentence(),
            'read_at'   => null,
        ];
    }

    /**
     * A message that has already been seen by the other side.
     */
    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }
}
