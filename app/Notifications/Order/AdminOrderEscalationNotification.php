<?php

namespace App\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminOrderEscalationNotification extends Notification
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
     * The data stored in the notifications table as JSON.
     *
     * Admin needs enough context to decide what to do:
     * assign manually, or cancel the order.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id'        => $this->order->id,
            'order_number'    => $this->order->order_number,
            'store_branch_id' => $this->order->store_branch_id,
            'attempts'        => $this->order->rider_assignment_attempts,
            'waiting_since'   => $this->order->rider_search_started_at,
            'total'           => $this->order->total,
            'message'         => 'No available rider found after 5 minutes. Manual assignment or action required.',
        ];
    }
}