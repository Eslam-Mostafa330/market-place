<?php

namespace App\Listeners\Order;

use App\Events\Order\OrderPlaced;
use App\Jobs\CustomerPreference\RefreshCustomerPreferences;
use Illuminate\Contracts\Queue\ShouldQueue;

class RefreshPreferencesForPlacedOrder implements ShouldQueue
{
    public bool $afterCommit = true;

    public int $tries = 3;

    /**
     * Feed the completed order into the customer's recommendation inputs.
     */
    public function handle(OrderPlaced $event): void
    {
        RefreshCustomerPreferences::throttledDispatch($event->customerId, 'order');
    }
}
