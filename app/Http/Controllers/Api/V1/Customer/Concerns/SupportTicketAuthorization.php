<?php

namespace App\Http\Controllers\Api\V1\Customer\Concerns;

use App\Models\SupportTicket;

trait SupportTicketAuthorization
{
    /**
     * Verifies that the given ticket was opened by the authenticated customer.
     *
     * @param SupportTicket $ticket
     */
    protected function authorizeTicket(SupportTicket $ticket): void
    {
        $customerId = auth()->id();

        abort_if(
            ! $customerId || $ticket->requester_id !== $customerId,
            404
        );
    }
}
