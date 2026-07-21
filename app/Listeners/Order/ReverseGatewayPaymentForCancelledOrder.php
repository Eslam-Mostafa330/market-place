<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderCancelled;
use App\Jobs\Order\ReverseOrderPaymentJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class ReverseGatewayPaymentForCancelledOrder implements ShouldQueue
{
    public bool $afterCommit = true;

    /**
     * Hand a cancelled card order to the gateway reversal job.
     *
     * The job owns the retry policy and the idempotency, because a refund that
     * fails on the first attempt still has to happen.
     */
    public function handle(OrderCancelled $event): void
    {
        if (! $event->paymentIntentId) {
            return;
        }

        ReverseOrderPaymentJob::dispatch($event->orderId);
    }
}
