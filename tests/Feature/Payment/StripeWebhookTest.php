<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Jobs\Order\ReverseOrderPaymentJob;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreBranch;
use App\Models\User;
use App\Services\Payment\StripeWebhookService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Stripe\Event;

beforeEach(function () {
    Queue::fake();

    $store  = Store::factory()->create();
    $branch = StoreBranch::factory()->for($store)->active(deliveryFee: 10)->create();

    $this->intentId = 'pi_' . Str::random(20);

    $this->order = Order::factory()->create([
        'customer_id'       => User::factory()->customer()->create()->id,
        'store_id'          => $store->id,
        'store_branch_id'   => $branch->id,
        'payment_method'    => PaymentMethod::VISA,
        'payment_status'    => PaymentStatus::PENDING,
        'order_status'      => OrderStatus::PENDING,
        'total'             => 110,
        'payment_intent_id' => $this->intentId,
    ]);
});

/**
 * Build a Stripe event without touching the network.
 *
 * constructFrom yields the same nested StripeObject shape the SDK delivers, so
 * the service is exercised exactly as it is in production.
 */
function stripeEvent(string $type, array $object): Event
{
    return Event::constructFrom([
        'id'   => 'evt_' . Str::random(20),
        'type' => $type,
        'data' => ['object' => $object],
    ]);
}

/**
 * A succeeded intent for this order, in minor units.
 */
function succeededIntent(string $intentId, int $amountReceived = 11000): Event
{
    return stripeEvent('payment_intent.succeeded', [
        'id'              => $intentId,
        'amount'          => $amountReceived,
        'amount_received' => $amountReceived,
    ]);
}

function failedIntent(string $intentId): Event
{
    return stripeEvent('payment_intent.payment_failed', [
        'id'     => $intentId,
        'amount' => 11000,
    ]);
}

function handleWebhook(Event $event): void
{
    app(StripeWebhookService::class)->process($event);
}

it('marks the order paid when the payment succeeds', function () {
    handleWebhook(succeededIntent($this->intentId));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID);
});

it('marks the payment failed when the gateway reports failure', function () {
    handleWebhook(failedIntent($this->intentId));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::FAILED);
});

/**
 * Stripe delivers at least once, so the same event can arrive repeatedly. The
 * handlers are written so a replay changes nothing rather than relying on a
 * ledger of processed event ids.
 */
it('is unchanged by a replayed success event', function () {
    handleWebhook(succeededIntent($this->intentId));
    $firstUpdatedAt = $this->order->fresh()->updated_at;

    handleWebhook(succeededIntent($this->intentId));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID)
        ->and($this->order->fresh()->updated_at->eq($firstUpdatedAt))->toBeTrue();
});

it('is unchanged by a replayed failure event', function () {
    handleWebhook(failedIntent($this->intentId));
    handleWebhook(failedIntent($this->intentId));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::FAILED);
});

/**
 * Ordering is not guaranteed either. A failure event that was delayed in transit
 * must never downgrade an order whose payment has since succeeded.
 */
it('does not let a late failure downgrade a paid order', function () {
    handleWebhook(succeededIntent($this->intentId));
    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID);

    handleWebhook(failedIntent($this->intentId));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID);
});

it('allows a retry to succeed after an earlier failure', function () {
    handleWebhook(failedIntent($this->intentId));
    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::FAILED);

    handleWebhook(succeededIntent($this->intentId));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID);
});

/**
 * A refund is terminal; no later gateway event may resurrect the order.
 */
it('does not resurrect a refunded order', function () {
    $this->order->update(['payment_status' => PaymentStatus::REFUNDED]);

    handleWebhook(succeededIntent($this->intentId));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::REFUNDED);
});

it('ignores a failure event for a refunded order', function () {
    $this->order->update(['payment_status' => PaymentStatus::REFUNDED]);

    handleWebhook(failedIntent($this->intentId));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::REFUNDED);
});

/**
 * The settled amount is checked against the order total. A mismatch means the
 * charge does not correspond to this order and must not settle it.
 */
it('refuses to mark an order paid for a mismatched amount', function () {
    handleWebhook(succeededIntent($this->intentId, amountReceived: 500));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

it('accepts an exact amount match', function () {
    $this->order->update(['total' => 171.71]);

    handleWebhook(succeededIntent($this->intentId, amountReceived: 17171));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID);
});

/**
 * Covers the race where the customer cancels while the charge is in flight: the
 * money arrived for an order that no longer exists commercially, so it is
 * refunded rather than marked paid.
 */
it('refunds a payment that lands on a cancelled order', function () {
    $this->order->update(['order_status' => OrderStatus::CANCELLED]);

    handleWebhook(succeededIntent($this->intentId));

    Queue::assertPushed(ReverseOrderPaymentJob::class, fn ($job) => $job->orderId === $this->order->id);

    expect($this->order->fresh()->payment_status)->not->toBe(PaymentStatus::PAID);
});

it('does not queue a reversal for a normal successful payment', function () {
    handleWebhook(succeededIntent($this->intentId));

    Queue::assertNotPushed(ReverseOrderPaymentJob::class);
});

it('ignores an event for an unknown payment intent', function () {
    handleWebhook(succeededIntent('pi_does_not_exist'));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

it('ignores an event type it does not handle', function () {
    handleWebhook(stripeEvent('charge.refunded', ['id' => $this->intentId]));

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});
