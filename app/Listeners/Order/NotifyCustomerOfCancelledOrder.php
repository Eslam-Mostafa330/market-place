<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderCancelled;
use App\Models\User;
use App\Notifications\Order\OrderCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerOfCancelledOrder implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 3;

    /**
     * Notify the order's customer.
     *
     * The recipient comes from the event rather than the authenticated user, so an
     * admin-initiated or system-initiated cancellation still reaches the customer.
     */
    public function handle(OrderCancelled $event): void
    {
        User::query()
            ->select('id')
            ->find($event->customerId)
            ?->notify(new OrderCancelledNotification(
                orderId: $event->orderId,
                orderNumber: $event->orderNumber,
                cancelledBy: $event->cancelledBy,
                cancellationNote: $event->cancellationNote,
            ));
    }
}
