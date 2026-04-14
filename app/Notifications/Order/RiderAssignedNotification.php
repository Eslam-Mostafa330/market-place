<?php

namespace App\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RiderAssignedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly string $orderId, private readonly string $branchSlug)
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
     * What the rider needs to act on:
     *  - where to pick up from (branch),
     *  - where to deliver (address), and
     *  - what they earn.
     */
    public function toDatabase(object $notifiable): array
    {
        $order = Order::select(
            'id',
            'order_number',
            'delivery_address_line',
            'delivery_city',
            'delivery_latitude',
            'delivery_longitude',
            'delivery_phone',
            'rider_earnings'
        )
        ->find($this->orderId);

        return [
            'order_id'           => $order->id,
            'order_number'       => $order->order_number,
            'store_branch_slug'  => $this->branchSlug,
            'delivery_address'   => $order->delivery_address_line,
            'delivery_city'      => $order->delivery_city,
            'delivery_latitude'  => $order->delivery_latitude,
            'delivery_longitude' => $order->delivery_longitude,
            'delivery_phone'     => $order->delivery_phone,
            'rider_earnings'     => $order->rider_earnings,
            'message'            => __('notifications.rider_assigned'),
        ];
    }
}