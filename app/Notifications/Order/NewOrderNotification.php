<?php

namespace App\Notifications\Order;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly string $orderId,
        private readonly string $orderNumber,
        private readonly float $total,
        private readonly int $itemsCount,
        private readonly string $branchName,
        private readonly string $storeName,
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
            'database' => 'new-order',
        ];
    }

    /**
     * Vendor needs to know a new order arrived at their branch
     * so they can accept or review it quickly.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id'          => $this->orderId,
            'order_number'      => $this->orderNumber,
            'store_branch_name' => $this->branchName,
            'store_name'        => $this->storeName,
            'items_count'       => $this->itemsCount,
            'total'             => $this->total,
            'message'           => __('notifications.new_order'),
        ];
    }
}