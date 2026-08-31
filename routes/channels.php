<?php

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// One support conversation: the customer who raised it, and the desk.
Broadcast::channel(
    'tickets.{ticketId}',
    fn (User $user, string $ticketId) => SupportTicket::find($ticketId)?->isVisibleTo($user) ?? false,
);

// The shared desk. Agents and admins only.
Broadcast::channel('support.queue', fn (User $user) => $user->staffsSupportDesk());

// Whether the desk is open is a yes or no with nobody's name on it, so any
// signed-in account may listen. Private only to keep it off the public wire.
Broadcast::channel('support.availability', fn () => true);
