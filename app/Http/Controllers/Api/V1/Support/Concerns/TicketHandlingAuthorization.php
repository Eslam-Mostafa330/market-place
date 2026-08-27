<?php

namespace App\Http\Controllers\Api\V1\Support\Concerns;

use App\Models\SupportTicket;

trait TicketHandlingAuthorization
{
    /**
     * Verifies that the authenticated agent may act on the given ticket.
     *
     * @param SupportTicket $ticket
     */
    protected function authorizeTicketHandling(SupportTicket $ticket): void
    {
        $agent = auth()->user();

        abort_if(
            $ticket->agent_id !== null && ! $ticket->isHandledBy($agent) && ! $agent->isAdmin(),
            403,
            __('support.handled_by_another_agent')
        );
    }
}
