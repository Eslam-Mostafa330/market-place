<?php

namespace App\Notifications\Order;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly string $orderId,
        private readonly string $orderNumber,
        private readonly string $cancelledBy,
        private readonly ?string $cancellationNote,
    ) {}

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
     * Specify the queue name for notification channels.
     *
     * @return array<string, string>
     */
    public function viaQueues(): array
    {
        return [
            'database' => 'cancel-order',
        ];
    }

    /**
     * Customer know that their order was cancelled
     * and who cancelled it so they can take action if needed.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id'          => $this->orderId,
            'order_number'      => $this->orderNumber,
            'cancelled_by'      => $this->cancelledBy,
            'cancellation_note' => $this->cancellationNote,
            'message'           => __('notifications.order_cancelled'),
        ];
    }
}