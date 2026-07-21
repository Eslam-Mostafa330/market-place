<?php

namespace Tests\Unit;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_every_status_has_a_transition_entry(): void
    {
        $transitions = OrderStatus::transitions();

        foreach (OrderStatus::cases() as $status) {
            $this->assertArrayHasKey($status->value, $transitions, "Missing transitions for {$status->name}");
        }
    }

    public function test_it_follows_the_happy_path(): void
    {
        $this->assertTrue(OrderStatus::PENDING->canTransitionTo(OrderStatus::ACCEPTED));
        $this->assertTrue(OrderStatus::ACCEPTED->canTransitionTo(OrderStatus::PREPARING));
        $this->assertTrue(OrderStatus::PREPARING->canTransitionTo(OrderStatus::WAITING_RIDER));
        $this->assertTrue(OrderStatus::WAITING_RIDER->canTransitionTo(OrderStatus::RIDER_ASSIGNED));
        $this->assertTrue(OrderStatus::RIDER_ASSIGNED->canTransitionTo(OrderStatus::PICKED_UP));
        $this->assertTrue(OrderStatus::PICKED_UP->canTransitionTo(OrderStatus::DELIVERED));
    }

    /**
     * A rider rejecting an assignment puts the order back in the search pool.
     */
    public function test_an_assigned_order_can_return_to_waiting_for_a_rider(): void
    {
        $this->assertTrue(OrderStatus::RIDER_ASSIGNED->canTransitionTo(OrderStatus::WAITING_RIDER));
    }

    public function test_it_rejects_skipping_ahead(): void
    {
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::DELIVERED));
        $this->assertFalse(OrderStatus::PENDING->canTransitionTo(OrderStatus::PICKED_UP));
        $this->assertFalse(OrderStatus::ACCEPTED->canTransitionTo(OrderStatus::RIDER_ASSIGNED));
    }

    public function test_it_rejects_moving_backwards(): void
    {
        $this->assertFalse(OrderStatus::PREPARING->canTransitionTo(OrderStatus::PENDING));
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::PICKED_UP));
    }

    public function test_every_live_status_can_be_cancelled(): void
    {
        $live = array_filter(
            OrderStatus::cases(),
            fn (OrderStatus $status) => ! in_array($status, OrderStatus::nonCancellableStatuses(), true),
        );

        foreach ($live as $status) {
            $this->assertTrue(
                $status->canTransitionTo(OrderStatus::CANCELLED),
                "{$status->name} should be cancellable",
            );
        }
    }

    public function test_terminal_statuses_cannot_be_cancelled(): void
    {
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::CANCELLED));
        $this->assertFalse(OrderStatus::CANCELLED->canTransitionTo(OrderStatus::CANCELLED));
    }

    public function test_terminal_statuses_are_reported_as_terminal(): void
    {
        $this->assertTrue(OrderStatus::DELIVERED->isTerminal());
        $this->assertTrue(OrderStatus::CANCELLED->isTerminal());
        $this->assertFalse(OrderStatus::PENDING->isTerminal());
        $this->assertFalse(OrderStatus::PICKED_UP->isTerminal());
    }

    /**
     * The two ways of expressing "cannot be cancelled" must not drift apart.
     */
    public function test_terminal_and_non_cancellable_agree(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertSame(
                $status->isTerminal(),
                in_array($status, OrderStatus::nonCancellableStatuses(), true),
                "{$status->name} disagrees between isTerminal() and nonCancellableStatuses()",
            );
        }
    }
}
