<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\Payment\StripeWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends BaseApiController
{
    public function __construct(private readonly StripeWebhookService $webhookService) {}

    /**
     * Receive and process Stripe webhook events.
     *
     * A signature failure returns 400 rather than bubbling into a 500. Stripe treats
     * 5xx as retryable and would otherwise redeliver a forged or malformed payload
     * on a backoff schedule for days.
     *
     * Everything past verification is delegated to StripeWebhookService, which
     * enforces exactly-once handling and the order state guards.
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature') ?? '',
                config('services.stripe.webhook_secret')
            );
        } catch (SignatureVerificationException|\UnexpectedValueException $exception) {
            Log::warning('Rejected Stripe webhook.', ['message' => $exception->getMessage()]);

            return response()->json(['message' => 'Invalid payload.'], 400);
        }

        $this->webhookService->process($event);

        return $this->apiResponse(null, 'Webhook received.');
    }
}
