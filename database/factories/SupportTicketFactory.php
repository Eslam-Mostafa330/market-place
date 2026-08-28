<?php

namespace Database\Factories;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'requester_id'    => User::factory()->customer(),
            'agent_id'        => null,
            'order_id'        => null,
            'subject'         => fake()->sentence(4),
            'category'        => TicketCategory::OTHER,
            'status'          => TicketStatus::OPEN,
            'last_message_at' => now(),
        ];
    }

    /**
     * A ticket an agent has already taken.
     */
    public function assignedTo(User $agent): static
    {
        return $this->state(fn () => [
            'agent_id' => $agent->id,
            'status'   => TicketStatus::ASSIGNED,
        ]);
    }

    /**
     * A ticket the agent has called done.
     */
    public function resolved(): static
    {
        return $this->state(fn () => ['status' => TicketStatus::RESOLVED]);
    }

    /**
     * A finished ticket that takes no more messages.
     */
    public function closed(): static
    {
        return $this->state(fn () => [
            'status'    => TicketStatus::CLOSED,
            'closed_at' => now(),
        ]);
    }
}
