<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreBranch;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    Queue::fake();

    $this->secret = 'whsec_test_secret';
    config(['services.stripe.webhook_secret' => $this->secret]);

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

function webhookPayload(string $intentId, int $amount = 11000): string
{
    return json_encode([
        'id'   => 'evt_' . Str::random(20),
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => [
            'id'              => $intentId,
            'amount'          => $amount,
            'amount_received' => $amount,
        ]],
    ]);
}

/**
 * Build the Stripe-Signature header the way Stripe does: an HMAC of
 * "<timestamp>.<raw body>" keyed on the endpoint's signing secret.
 */
function signatureHeader(string $payload, string $secret, ?int $timestamp = null): string
{
    $timestamp ??= time();

    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

    return "t={$timestamp},v1={$signature}";
}

function postWebhook(string $payload, ?string $signature): Illuminate\Testing\TestResponse
{
    $server = ['CONTENT_TYPE' => 'application/json'];

    if ($signature !== null) {
        $server['HTTP_STRIPE_SIGNATURE'] = $signature;
    }

    return test()->call('POST', '/api/v1/stripe/webhook', [], [], [], $server, $payload);
}

it('processes a correctly signed event', function () {
    $payload = webhookPayload($this->intentId);

    postWebhook($payload, signatureHeader($payload, $this->secret))->assertOk();

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PAID);
});

/**
 * Stripe treats 5xx as retryable and redelivers on a backoff for days. A forged
 * or corrupt payload is never going to become valid, so it has to be rejected
 * with a 4xx or it turns into an endless retry loop.
 */
it('rejects a wrongly signed event with 400 rather than 500', function () {
    $payload = webhookPayload($this->intentId);

    postWebhook($payload, signatureHeader($payload, 'whsec_the_wrong_secret'))
        ->assertStatus(400);

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

it('rejects a missing signature header with 400', function () {
    postWebhook(webhookPayload($this->intentId), null)->assertStatus(400);

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

it('rejects a malformed signature header with 400', function () {
    postWebhook(webhookPayload($this->intentId), 'not-a-signature')->assertStatus(400);

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

/**
 * The signature covers the body, so altering the payload after signing — say,
 * pointing the event at a different order — invalidates it.
 */
it('rejects a payload tampered with after signing', function () {
    $original  = webhookPayload($this->intentId);
    $signature = signatureHeader($original, $this->secret);

    $tampered = webhookPayload($this->intentId, amount: 1);

    postWebhook($tampered, $signature)->assertStatus(400);

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

/**
 * Stripe's verifier enforces a timestamp tolerance, which is what stops an old
 * captured request being replayed later.
 */
it('rejects a signature outside the timestamp tolerance', function () {
    $payload = webhookPayload($this->intentId);

    $stale = signatureHeader($payload, $this->secret, timestamp: time() - 3600);

    postWebhook($payload, $stale)->assertStatus(400);

    expect($this->order->fresh()->payment_status)->toBe(PaymentStatus::PENDING);
});

it('rejects invalid json with 400', function () {
    $payload = 'this is not json';

    postWebhook($payload, signatureHeader($payload, $this->secret))->assertStatus(400);
});

/**
 * Gateway callbacks arrive in bursts from a small pool of provider IPs. Without an
 * exemption they fall into the per-IP "forms" bucket, which allows 20 a minute, and
 * Stripe starts collecting 429s.
 *
 * The throttle has no environment exemption, so exceeding that limit here is a real
 * assertion rather than an artefact of the test environment.
 */
it('is not throttled by the per-ip form limit', function () {
    foreach (range(1, 25) as $attempt) {
        $payload = webhookPayload($this->intentId);

        postWebhook($payload, signatureHeader($payload, $this->secret))->assertOk();
    }
});
