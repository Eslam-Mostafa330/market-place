<?php

namespace App\Services\Payment;

use App\Models\Order;
use Stripe\StripeClient;

class PaymentService
{
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
}