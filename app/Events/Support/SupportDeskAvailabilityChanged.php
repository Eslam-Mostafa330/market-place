<?php

namespace App\Events\Support;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

class SupportDeskAvailabilityChanged implements ShouldBroadcastNow
{
    use Dispatchable;

    /**
     * @param array<string, mixed> $snapshot What a customer is allowed to know.
     */
    public function __construct(public readonly array $snapshot) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('support.availability');
    }

    public function broadcastAs(): string
    {
        return 'desk.availability';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->snapshot;
    }
}
