<?php

namespace App\Services\Payment;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Stripe\StripeClient;

class PaymentService
{
    /**
     * PaymentIntent statuses that may still be cancelled at the gateway.
     */
    private const CANCELABLE_INTENT_STATUSES = [
        'requires_payment_method',
        'requires_capture',
        'requires_confirmation',
        'requires_action',
        'processing',
    ];

    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create a Stripe PaymentIntent for the given order and store its identifier.
     *
     * Returns the client secret required by the frontend to complete the payment.
     * Uses an idempotency key to prevent duplicate PaymentIntent creation.
     *
     * @param Order $order
     *
     * @return array{client_secret: string, payment_intent_id: string}
    */
    public function createPaymentIntent(Order $order): array
    {
        $intent = $this->stripe->paymentIntents->create(
            [
                'amount'   => (int) round($order->total * 100),
                'currency' => 'usd',

                'automatic_payment_methods' => [
                    'enabled'         => true,
                    'allow_redirects' => 'never',
                ],

                'metadata' => [
                    'order_id'     => $order->id,
                    'order_number' => $order->order_number,
                    'customer_id'  => $order->customer_id,
                ],
            ],
            [
                'idempotency_key' => 'pi_' . $order->id,
            ]
        );

        $order->update([
            'payment_intent_id' => $intent->id,
        ]);

        return [
            'client_secret'     => $intent->client_secret,
            'payment_intent_id' => $intent->id,
        ];
    }

    /**
     * Reverse an order's payment at the gateway.
     *
     * Refunds captured payments or cancels pending payment intents.
     * Safe to retry using idempotency keys.
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function reversePayment(Order $order): bool
    {
        if (! $order->payment_intent_id) {
            return false;
        }

        if ($order->payment_status === PaymentStatus::PAID) {
            $this->stripe->refunds->create(
                [
                    'payment_intent' => $order->payment_intent_id,
                    'reason'         => 'requested_by_customer',
                    'metadata'       => ['order_id' => $order->id, 'order_number' => $order->order_number],
                ],
                ['idempotency_key' => 're_' . $order->id]
            );

            return true;
        }

        $intent = $this->stripe->paymentIntents->retrieve($order->payment_intent_id);

        if (! in_array($intent->status, self::CANCELABLE_INTENT_STATUSES, true)) {
            return false;
        }

        $this->stripe->paymentIntents->cancel(
            $order->payment_intent_id,
            [],
            ['idempotency_key' => 'cx_' . $order->id]
        );

        return true;
    }
}