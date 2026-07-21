<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderStatusChanged;
use App\Models\User;
use App\Notifications\Order\OrderStatusUpdatedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCustomerOfStatusChange implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 3;

    /**
     * Tell the customer their order moved forward.
     *
     * The status comes from the event, so the message and the status it reports
     * always describe the same moment even if the order has advanced again by the
     * time this runs.
     */
    public function handle(OrderStatusChanged $event): void
    {
        User::query()
            ->select('id')
            ->find($event->customerId)
            ?->notify(new OrderStatusUpdatedNotification(
                $event->orderId,
                $event->orderNumber,
                $event->status,
                $event->customerMessage,
            ));
    }
}
