<?php

namespace App\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCancelledNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly Order $order)
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
     * Customer know that their order was cancelled
     * and who cancelled it so they can take action if needed.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id'          => $this->order->id,
            'order_number'      => $this->order->order_number,
            'cancelled_by'      => $this->order->cancelled_by,
            'cancellation_note' => $this->order->cancellation_note,
            'message'           => __('notifications.order_cancelled'),
        ];
    }
}