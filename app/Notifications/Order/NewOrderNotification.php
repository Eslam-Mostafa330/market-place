<?php

namespace App\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly Order $order,
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
     * Vendor needs to know a new order arrived at their branch
     * so they can accept or review it quickly.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'order_id'          => $this->order->id,
            'order_number'      => $this->order->order_number,
            'store_branch_name' => $this->branchName,
            'store_name'        => $this->storeName,
            'items_count'       => $this->itemsCount,
            'total'             => $this->order->total,
            'message'           => __('notifications.new_order'),
        ];
    }
}