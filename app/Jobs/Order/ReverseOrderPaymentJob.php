<?php

namespace App\Jobs\Order;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payment\PaymentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ReverseOrderPaymentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Money must not be abandoned after a single transient gateway failure.
     */
    public int $tries = 5;

    /**
     * Escalating backoff, in seconds, across the retry attempts.
     */
    public array $backoff = [30, 120, 600, 1800];

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly string $orderId)
    {
        $this->onQueue('payments');
    }

    /**
     * Reverse the payment for a cancelled order.
     *
     * Safe to retry because payment operations are idempotent.
     */
    public function handle(PaymentService $paymentService): void
    {
        $order = Order::query()
            ->select('id', 'order_number', 'order_status', 'payment_status', 'payment_intent_id')
            ->find($this->orderId);

        if (! $order || $order->order_status !== OrderStatus::CANCELLED) {
            return;
        }

        if ($order->payment_status === PaymentStatus::REFUNDED) {
            return;
        }

        $reversed = $paymentService->reversePayment($order);

        if (! $reversed) {
            return;
        }

        $order->update(['payment_status' => PaymentStatus::REFUNDED]);
    }

    /**
     * Alert operators when the gateway could not be reversed.
     *
     * At this point the customer holds a cancelled order against a live charge, so
     * it needs manual reconciliation rather than silent failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Failed to reverse payment for cancelled order.', [
            'order_id' => $this->orderId,
            'message'  => $exception->getMessage(),
        ]);
    }
}
