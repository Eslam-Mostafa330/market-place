<?php

namespace App\Notifications\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Validation\Rules\Enum;

class OrderStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly string $orderId,
        private readonly string $orderNumber,
        private readonly OrderStatus $orderStatus,
        private readonly string $message
    ) {}

    public function viaQueues(): array
    {
        return [
            'database' => 'order-status-change',
        ];
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
     * Generic status update notification for the customer.
     *
     * Pass the message from the caller so this notification covers all status updates.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id'     => $this->orderId,
            'order_number' => $this->orderNumber,
            'order_status' => $this->orderStatus->value,
            'message'      => $this->message,
        ];
    }
}