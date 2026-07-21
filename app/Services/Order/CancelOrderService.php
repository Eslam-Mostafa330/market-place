<?php

namespace App\Services\Order;

use App\Enums\CancellationReason;
use App\Enums\OrderStatus;
use App\Events\Order\OrderCancelled;
use App\Models\Coupon;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Product\ProductStockService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class CancelOrderService
{
    public function __construct(private readonly ProductStockService $stockService) {}

    /**
     * Cancel an order and reverse its side effects.
     *
     * Uses a row lock to prevent concurrent cancellations.
     * Payment reversal and notifications run asynchronously after the transaction commits.
     *
     * @param string             $orderId     Order being cancelled
     * @param CancellationReason $reason      Structured cancellation reason
     * @param string|null        $note        Free-text detail
     * @param string             $cancelledBy Actor label ('customer' | 'admin')
     * @param string|null        $customerId  Restrict cancellation to the customer's own order
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    public function cancel(string $orderId, CancellationReason $reason, ?string $note, string $cancelledBy, ?string $customerId = null): Order
    {
        $order = DB::transaction(function () use ($orderId, $reason, $note, $cancelledBy, $customerId) {
            $order = Order::query()
                ->where('id', $orderId)
                ->when($customerId, fn ($query) => $query->where('customer_id', $customerId))
                ->lockForUpdate()
                ->firstOrFail();

            $this->ensureCancellable($order);

            $order->update([
                'order_status'        => OrderStatus::CANCELLED,
                'cancelled_by'        => $cancelledBy,
                'cancellation_reason' => $reason,
                'cancellation_note'   => $note,
            ]);

            $this->restoreStock($order);
            $this->releaseCoupon($order);
            $this->refundWallet($order);

            return $order;
        });

        event(OrderCancelled::from($order));

        return $order;
    }

    /**
     * Verify the order is in a cancellable status.
     *
     * Asks the status graph rather than re-listing the terminal statuses here, so
     * cancellability stays defined in one place alongside every other transition.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
     */
    private function ensureCancellable(Order $order): void
    {
        if (! $order->order_status->canTransitionTo(OrderStatus::CANCELLED)) {
            throw new UnprocessableEntityHttpException(__('orders.cannot_cancel'));
        }
    }

    /**
     * Return every unit reserved by this order to the catalog.
     *
     * Order items are already merged per product at placement, so each product
     * appears once and the restore mirrors the original decrement exactly.
     */
    private function restoreStock(Order $order): void
    {
        $quantities = OrderItem::query()
            ->where('order_id', $order->id)
            ->pluck('quantity', 'product_id')
            ->map(fn ($quantity) => (int) $quantity)
            ->all();

        $this->stockService->restore($quantities);
    }

    /**
     * Give back the coupon redemption this order consumed.
     *
     * The per-user limit is enforced by counting non-cancelled orders, so that side
     * recovers on its own; only the aggregate counter needs adjusting here. The
     * guard on used_count keeps the counter from going negative if it was ever
     * reconciled by hand.
     */
    private function releaseCoupon(Order $order): void
    {
        if (! $order->coupon_id) {
            return;
        }

        Coupon::whereKey($order->coupon_id)
            ->where('used_count', '>', 0)
            ->update(['used_count' => DB::raw('used_count - 1')]);
    }

    /**
     * Credit back any wallet balance spent on this order.
     *
     * Wallet is debited at placement regardless of payment method, so it must be
     * returned even for an order that was never actually paid.
     */
    private function refundWallet(Order $order): void
    {
        $walletDiscount = (float) $order->wallet_discount;

        if ($walletDiscount <= 0) {
            return;
        }

        CustomerProfile::where('user_id', $order->customer_id)
            ->increment('wallet_balance', $walletDiscount);
    }

}
