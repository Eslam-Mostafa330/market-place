<?php

namespace App\Events\Support;

use App\Models\SupportTicket;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Broadcast ticket updates to the support desk and customer.
 */
class SupportTicketUpdated implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * Store the ticket data when the event is created.
     *
     * @var array<string, mixed>
     */
    public readonly array $payload;

    public function __construct(SupportTicket $ticket)
    {
        $ticket->loadMissing('agent:id,name');

        $this->payload = [
            'id'              => $ticket->id,
            'subject'         => $ticket->subject,
            'category'        => $ticket->category,
            'status'          => $ticket->status,
            'agent_id'        => $ticket->agent_id,
            'agent_name'      => $ticket->agent?->name,
            'last_message_at' => $ticket->last_message_at,
        ];
    }

    /**
     * Broadcast to the support queue and the ticket conversation.
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('support.queue'),
            new PrivateChannel('tickets.'.$this->payload['id']),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
