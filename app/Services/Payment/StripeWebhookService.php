<?php

namespace App\Services\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\Order\ReverseOrderPaymentJob;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Event;

class StripeWebhookService
{
    /**
     * Process a verified Stripe event.
     *
     * Stripe delivers at least once and does not guarantee ordering, so the same
     * event can arrive repeatedly and a stale event can arrive after a newer one.
     * Rather than tracking seen event ids, each handler below is written so that
     * applying it twice — or applying an out-of-date event — is a no-op: every
     * transition is guarded on the order's current payment status, under a row lock
     * that serializes concurrent deliveries.
     *
     * That makes replay protection a property of the state transitions themselves,
     * which is what has to be correct regardless; a separate ledger of processed
     * events would add a table without changing any outcome.
     */
    public function process(Event $event): void
    {
        match ($event->type) {
            'payment_intent.succeeded'      => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default                         => null,
        };
    }

    /**
     * Mark the order paid once the gateway confirms the funds.
     *
     * The order row is locked so this cannot interleave with a concurrent
     * cancellation. Several situations are deliberately not treated as a payment:
     *
     * - A refunded order is terminal; a late success event must not resurrect it.
     * - An already-paid order is a duplicate delivery and needs no second write.
     * - A cancelled order that just received funds is refunded instead, covering
     *   the race where the customer cancels while the charge is in flight.
     * - A mismatched amount is never marked paid; it is surfaced for review.
     */
    private function handlePaymentSucceeded(object $paymentIntent): void
    {
        DB::transaction(function () use ($paymentIntent) {
            $order = $this->lockOrder($paymentIntent->id);

            if (! $order || $order->payment_status === PaymentStatus::REFUNDED || $order->payment_status === PaymentStatus::PAID) {
                return;
            }

            if (! $this->amountMatches($order, $paymentIntent)) {
                Log::error('Stripe payment amount does not match order total.', [
                    'order_id'        => $order->id,
                    'order_total'     => $order->total,
                    'amount_received' => $paymentIntent->amount_received ?? null,
                ]);

                return;
            }

            if ($order->order_status === OrderStatus::CANCELLED) {
                Log::warning('Payment succeeded for a cancelled order; reversing.', ['order_id' => $order->id]);

                ReverseOrderPaymentJob::dispatch($order->id);

                return;
            }

            $order->update(['payment_status' => PaymentStatus::PAID]);
        });
    }

    /**
     * Mark the order's payment failed.
     *
     * Only a payment still awaiting settlement can fail. Because events can arrive
     * out of order, a failure event for an order that has since been paid or
     * refunded is stale and is ignored rather than downgrading the order.
     */
    private function handlePaymentFailed(object $paymentIntent): void
    {
        DB::transaction(function () use ($paymentIntent) {
            $order = $this->lockOrder($paymentIntent->id);

            if (! $order || $order->payment_status !== PaymentStatus::PENDING) {
                return;
            }

            $order->update(['payment_status' => PaymentStatus::FAILED]);
        });
    }

    /**
     * Load and lock the order behind a payment intent.
     */
    private function lockOrder(string $paymentIntentId): ?Order
    {
        return Order::query()
            ->select('id', 'order_status', 'payment_status', 'total')
            ->where('payment_intent_id', $paymentIntentId)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Confirm the settled amount equals the order total, in minor units.
     */
    private function amountMatches(Order $order, object $paymentIntent): bool
    {
        $expected = (int) round((float) $order->total * 100);
        $received = (int) ($paymentIntent->amount_received ?? $paymentIntent->amount ?? 0);

        return $expected === $received;
    }
}
