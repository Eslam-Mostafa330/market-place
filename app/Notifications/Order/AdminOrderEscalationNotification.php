<?php

namespace App\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminOrderEscalationNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly string $orderId)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * The data stored in the notifications table as JSON.
     *
     * Admin needs enough context to decide what to do:
     * assign manually, or cancel the order.
     */
    public function toDatabase(object $notifiable): array
    {
        $order = Order::query()
        ->select(
            'id',
            'order_number',
            'store_branch_id',
            'rider_assignment_attempts',
            'rider_search_started_at',
            'total'
        )
        ->find($this->orderId);

        return [
            'order_id'        => $order->id,
            'order_number'    => $order->order_number,
            'store_branch_id' => $order->store_branch_id,
            'attempts'        => $order->rider_assignment_attempts,
            'waiting_since'   => $order->rider_search_started_at,
            'total'           => $order->total,
            'message'         => __('notifications.order_escalation'),
        ];
    }
}