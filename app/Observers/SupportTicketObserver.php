<?php

namespace App\Observers;

use App\Events\Support\SupportTicketUpdated;
use App\Models\SupportTicket;

class SupportTicketObserver
{
    /**
     * Broadcast when a ticket moves or receives a new message.
     */
    public function updated(SupportTicket $ticket): void
    {
        if ($ticket->wasChanged(['status', 'agent_id', 'last_message_at'])) {
            SupportTicketUpdated::dispatch($ticket);
        }
    }
}
