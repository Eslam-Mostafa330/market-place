<?php

namespace App\Events\Support;

use App\Models\SupportMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class SupportMessageSent implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(public readonly SupportMessage $message) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tickets.'.$this->message->ticket_id);
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Return the message data needed by both clients.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id'          => $this->message->id,
            'ticket_id'   => $this->message->ticket_id,
            'body'        => $this->message->body,
            'sender_name' => $this->message->sender->name,
            'from_desk'   => $this->message->sender->staffsSupportDesk(),
            'created_at'  => $this->message->created_at,
        ];
    }
}
