<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderPlaced;
use App\Models\Order;
use App\Models\User;
use App\Notifications\Order\NewOrderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class NotifyVendorOfPlacedOrder implements ShouldQueue
{
    /**
     * Only run once the order transaction has committed.
     *
     * Without this, a transaction that rolls back after dispatching would still
     * have told the vendor about an order that does not exist.
     */
    public bool $afterCommit = true;

    public int $tries = 3;

    public function handle(OrderPlaced $event): void
    {
        $order = Order::query()
            ->select('id', 'order_number', 'total', 'store_id', 'store_branch_id')
            ->with([
                'store:id,name,vendor_profile_id',
                'store.vendorProfile:id,user_id',
                'storeBranch:id,name',
            ])
            ->withCount('items')
            ->find($event->orderId);

        if (! $order) {
            return;
        }

        $vendorUserId = $order->store?->vendorProfile?->user_id;

        if (! $vendorUserId) {
            Log::warning('Order placed but no vendor user found to notify.', [
                'order_id' => $order->id,
                'store_id' => $order->store_id,
            ]);

            return;
        }

        User::query()->select('id')->find($vendorUserId)?->notify(new NewOrderNotification(
            orderId: $order->id,
            orderNumber: $order->order_number,
            total: $order->total,
            itemsCount: $order->items_count,
            branchName: $order->storeBranch->name,
            storeName: $order->store->name,
        ));
    }
}
