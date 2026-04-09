<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\Order;
use Illuminate\Http\Request;
use Stripe\Webhook;

class StripeWebhookController extends BaseApiController
{
    /**
     * Receive and process Stripe webhook events after validating the signature,
     * then dispatch them to update order payment status accordingly.
     */
    public function handle(Request $request)
    {
        $event = Webhook::constructEvent(
            $request->getContent(),
            $request->header('Stripe-Signature'),
            config('services.stripe.webhook_secret')
        );

        match ($event->type) {
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => null,
        };

        return $this->apiResponse(null, 'Webhook received.');
    }

    /**
     * Update the order payment status to paid when Stripe confirms a successful payment intent.
     */
    private function handlePaymentSucceeded(object $paymentIntent): void
    {
        Order::where('payment_intent_id', $paymentIntent->id)
            ->first()
            ?->update(['payment_status' => PaymentStatus::PAID]);
    }

    /**
     * Update the order payment status to failed when Stripe reports a failed payment intent.
     */
    private function handlePaymentFailed(object $paymentIntent): void
    {
        Order::where('payment_intent_id', $paymentIntent->id)
            ->first()
            ?->update(['payment_status' => PaymentStatus::FAILED]);
    }
}