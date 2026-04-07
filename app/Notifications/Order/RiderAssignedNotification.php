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
    public function __construct(private readonly Order $order, private readonly string $branchSlug)
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
        return [
            'order_id'          => $this->order->id,
            'order_number'      => $this->order->order_number,
            'store_branch_slug' => $this->branchSlug,
            'delivery_address'  => $this->order->delivery_address_line,
            'delivery_city'     => $this->order->delivery_city,
            'delivery_lat'      => $this->order->delivery_latitude,
            'delivery_lng'      => $this->order->delivery_longitude,
            'delivery_phone'    => $this->order->delivery_phone,
            'rider_earnings'    => $this->order->rider_earnings,
            'message'           => __('notifications.rider_assigned'),
        ];
    }
}