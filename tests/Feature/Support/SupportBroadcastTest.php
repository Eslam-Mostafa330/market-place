<?php

use App\Events\Support\SupportDeskAvailabilityChanged;
use App\Events\Support\SupportMessageSent;
use App\Events\Support\SupportTicketUpdated;
use App\Models\SupportTicket;
use App\Models\User;
use App\Enums\AgentAvailability;
use App\Services\Support\SupportMessageService;
use App\Services\Support\SupportPresenceService;
use App\Services\Support\SupportTicketService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->customer = User::factory()->customer()->create();
    $this->agent    = User::factory()->support()->create(['name' => 'Ali']);
    $this->ticket   = SupportTicket::factory()->assignedTo($this->agent)->create([
        'requester_id' => $this->customer->id,
    ]);
});

it('announces an agent reply on the ticket channel', function () {
    Event::fake([SupportMessageSent::class]);

    app(SupportMessageService::class)->post($this->ticket, $this->agent, 'On it now');

    Event::assertDispatched(SupportMessageSent::class, function (SupportMessageSent $event) {
        $payload = $event->broadcastWith();

        return $event->broadcastOn()->name === 'private-tickets.'.$this->ticket->id
            && $event->broadcastAs() === 'message.sent'
            && $payload['body'] === 'On it now'
            && $payload['sender_name'] === 'Ali'
            && $payload['from_desk'] === true;
    });
});

it('announces a customer reply as coming from outside the desk', function () {
    Event::fake([SupportMessageSent::class]);

    app(SupportMessageService::class)->post($this->ticket, $this->customer, 'Any update?');

    Event::assertDispatched(SupportMessageSent::class, fn (SupportMessageSent $event) => $event->broadcastWith()['from_desk'] === false);
});

it('says nothing when the message was refused', function () {
    Event::fake([SupportMessageSent::class]);

    $closed = SupportTicket::factory()->closed()->create(['requester_id' => $this->customer->id]);

    expect(fn () => app(SupportMessageService::class)->post($closed, $this->customer, 'hello'))
        ->toThrow(Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException::class);

    Event::assertNotDispatched(SupportMessageSent::class);
});

it('holds the opening message until the ticket is committed', function () {
    $seenAtLevel = null;
    $baseline    = DB::transactionLevel();

    Event::listen(SupportMessageSent::class, function () use (&$seenAtLevel) {
        $seenAtLevel = DB::transactionLevel();
    });

    app(SupportTicketService::class)->openTicket($this->customer, [
        'subject'  => 'Nothing arrived',
        'message'  => 'Please help.',
        'category' => App\Enums\TicketCategory::OTHER->value,
    ]);

    // openTicket writes the ticket and posts the first message in a transaction
    // of its own. Announcing the message from inside it would offer subscribers
    // a row no other connection can read, and would show as a level above the
    // one the test itself holds.
    expect($seenAtLevel)->toBe($baseline);
});

/*
 * The shared desk channel
 */
it('tells the desk when a customer opens a ticket', function () {
    Event::fake([SupportTicketUpdated::class]);

    $ticket = app(SupportTicketService::class)->openTicket($this->customer, [
        'subject'  => 'Nothing arrived',
        'message'  => 'Please help.',
        'category' => App\Enums\TicketCategory::OTHER->value,
    ]);

    Event::assertDispatched(SupportTicketUpdated::class, function (SupportTicketUpdated $event) use ($ticket) {
        $payload = $event->broadcastWith();

        $channels = collect($event->broadcastOn())->pluck('name');

        return $channels->contains('private-support.queue')
            && $channels->contains('private-tickets.'.$ticket->id)
            && $event->broadcastAs() === 'ticket.updated'
            && $payload['id'] === $ticket->id
            && $payload['agent_id'] === null;
    });
});

/**
 * The desk sorts its queue on the message time, and a nested transaction
 * commits before the one holding it, so a second announcement made when the
 * row was still bare would arrive last and sort the newest ticket to the
 * bottom. Opening a ticket says one thing, and says it with the time on it.
 */
it('announces a new ticket once, with the time the desk sorts on', function () {
    Event::fake([SupportTicketUpdated::class]);

    app(SupportTicketService::class)->openTicket($this->customer, [
        'subject'  => 'Nothing arrived',
        'message'  => 'Please help.',
        'category' => App\Enums\TicketCategory::OTHER->value,
    ]);

    Event::assertDispatchedTimes(SupportTicketUpdated::class, 1);
    Event::assertDispatched(SupportTicketUpdated::class, fn (SupportTicketUpdated $e) => $e->broadcastWith()['last_message_at'] !== null);
});

it('tells the desk a ticket has been spoken on', function () {
    $this->travel(1)->minute();

    Event::fake([SupportTicketUpdated::class]);

    app(SupportMessageService::class)->post($this->ticket, $this->customer, 'Any update?');

    Event::assertDispatched(SupportTicketUpdated::class, function (SupportTicketUpdated $event) {
        return $event->broadcastWith()['id'] === $this->ticket->id
            && $event->broadcastWith()['last_message_at'] !== null;
    });
});

it('stays off the queue channel when a ticket has not moved', function () {
    Event::fake([SupportTicketUpdated::class]);

    app(SupportMessageService::class)->markRead($this->ticket, $this->agent);

    Event::assertNotDispatched(SupportTicketUpdated::class);
});

it('tells the desk when a reply reopens a resolved ticket', function () {
    Event::fake([SupportTicketUpdated::class]);

    $resolved = SupportTicket::factory()->assignedTo($this->agent)->resolved()->create([
        'requester_id' => $this->customer->id,
    ]);

    app(SupportMessageService::class)->post($resolved, $this->customer, 'It is still broken');

    Event::assertDispatched(SupportTicketUpdated::class, fn (SupportTicketUpdated $e) => $e->broadcastWith()['status'] === App\Enums\TicketStatus::ASSIGNED);
});

it('tells the desk who claimed a ticket', function () {
    Event::fake([SupportTicketUpdated::class]);

    $free = SupportTicket::factory()->create(['requester_id' => $this->customer->id]);

    app(SupportTicketService::class)->claim($free, $this->agent);

    Event::assertDispatched(SupportTicketUpdated::class, fn (SupportTicketUpdated $e) => $e->broadcastWith()['agent_id'] === $this->agent->id);
});

it('tells the desk when a ticket is handed back', function () {
    Event::fake([SupportTicketUpdated::class]);

    app(SupportTicketService::class)->release($this->ticket);

    Event::assertDispatched(SupportTicketUpdated::class, fn (SupportTicketUpdated $e) => $e->broadcastWith()['agent_id'] === null);
});

/*
 * Who may listen, proved through the real handshake endpoint
 */
function subscribeTo(string $channel)
{
    config(['broadcasting.default' => 'reverb']);
    require base_path('routes/channels.php');

    return test()->postJson('/api/v1/broadcasting/auth', [
        'socket_id'    => '123.456',
        'channel_name' => $channel,
    ]);
}

it('lets the customer listen to their own ticket', function () {
    $this->actingAs($this->customer);

    subscribeTo('private-tickets.'.$this->ticket->id)->assertOk();
});

it('lets the desk listen to a ticket', function () {
    $this->actingAs($this->agent);

    subscribeTo('private-tickets.'.$this->ticket->id)->assertOk();
});

it('keeps a stranger off someone else conversation', function () {
    $stranger = User::factory()->customer()->create();

    $this->actingAs($stranger);

    subscribeTo('private-tickets.'.$this->ticket->id)->assertForbidden();
});

it('keeps a customer off the shared desk channel', function () {
    $this->actingAs($this->customer);

    subscribeTo('private-support.queue')->assertForbidden();
});

it('lets an agent onto the shared desk channel', function () {
    $this->actingAs($this->agent);

    subscribeTo('private-support.queue')->assertOk();
});

/**
 * The handshake carries no route name, so it misses the staff exemption and
 * lands on whatever the method says. Counted as a write it shared one bucket
 * with everything else the account submits, and a reconnect re-authorises
 * every open channel without anyone touching the page.
 */
it('does not spend a customer write budget on reconnecting', function () {
    $this->actingAs($this->customer);

    foreach (range(1, 20) as $ignored) {
        subscribeTo('private-tickets.'.$this->ticket->id)->assertOk();
    }

    $this->postJson("/api/v1/customer/support/tickets/{$this->ticket->id}/messages", ['body' => 'Still here'])
        ->assertCreated();
});

it('lets an agent open more conversations than a form limit allows', function () {
    $this->actingAs($this->agent);

    foreach (range(1, 25) as $ignored) {
        subscribeTo('private-tickets.'.$this->ticket->id)->assertOk();
    }
});

/*
 * The desk opening and closing
 */
it('announces the desk opening, and only when it actually opens', function () {
    Event::fake([SupportDeskAvailabilityChanged::class]);

    app(SupportPresenceService::class)->heartbeat($this->agent, AgentAvailability::ONLINE);

    Event::assertDispatched(SupportDeskAvailabilityChanged::class, function (SupportDeskAvailabilityChanged $event) {
        return $event->broadcastOn()->name === 'private-support.availability'
            && $event->broadcastAs() === 'desk.availability'
            && $event->broadcastWith()['support_available'] === true;
    });

    Event::fake([SupportDeskAvailabilityChanged::class]);
    app(SupportPresenceService::class)->heartbeat($this->agent, AgentAvailability::ONLINE);
    Event::assertNotDispatched(SupportDeskAvailabilityChanged::class);
});

it('announces the desk closing when the last agent leaves', function () {
    app(SupportPresenceService::class)->heartbeat($this->agent, AgentAvailability::ONLINE);

    Event::fake([SupportDeskAvailabilityChanged::class]);

    app(SupportPresenceService::class)->heartbeat($this->agent, AgentAvailability::OFFLINE);

    Event::assertDispatched(SupportDeskAvailabilityChanged::class, fn ($e) => $e->broadcastWith()['support_available'] === false);
});

it('never tells the customer who is on shift', function () {
    Event::fake([SupportDeskAvailabilityChanged::class]);

    app(SupportPresenceService::class)->heartbeat($this->agent, AgentAvailability::ONLINE);

    Event::assertDispatched(SupportDeskAvailabilityChanged::class, function ($event) {
        $payload = json_encode($event->broadcastWith());

        return array_keys($event->broadcastWith()) === ['support_available', 'message']
            && ! str_contains($payload, $this->agent->name)
            && ! str_contains($payload, $this->agent->id);
    });
});

it('tells the customer their ticket was closed', function () {
    Event::fake([SupportTicketUpdated::class]);

    app(SupportTicketService::class)->changeStatus($this->ticket, App\Enums\TicketStatus::CLOSED);

    Event::assertDispatched(SupportTicketUpdated::class, function (SupportTicketUpdated $event) {
        $channels = collect($event->broadcastOn())->pluck('name');

        return $channels->contains('private-tickets.'.$this->ticket->id)
            && $event->broadcastWith()['status'] === App\Enums\TicketStatus::CLOSED;
    });
});

it('announces a desk that went quiet without signing out', function () {
    app(SupportPresenceService::class)->heartbeat($this->agent, AgentAvailability::ONLINE);

    $this->travel(config('support.agent_presence_ttl_minutes') + 1)->minutes();

    Event::fake([SupportDeskAvailabilityChanged::class]);

    $this->actingAs($this->customer)
        ->getJson('/api/v1/customer/support/availability')
        ->assertJsonPath('data.support_available', false);

    Event::assertDispatched(SupportDeskAvailabilityChanged::class, fn ($e) => $e->broadcastWith()['support_available'] === false);
});

it('lets the sweep notice a desk that emptied itself', function () {
    app(SupportPresenceService::class)->heartbeat($this->agent, AgentAvailability::ONLINE);

    $this->travel(config('support.agent_presence_ttl_minutes') + 1)->minutes();

    Event::fake([SupportDeskAvailabilityChanged::class]);

    $this->artisan('support:sweep-tickets')->assertSuccessful();

    Event::assertDispatched(SupportDeskAvailabilityChanged::class, fn ($e) => $e->broadcastWith()['support_available'] === false);
});

it('stays quiet while nothing about the desk changed', function () {
    app(SupportPresenceService::class)->heartbeat($this->agent, AgentAvailability::ONLINE);

    Event::fake([SupportDeskAvailabilityChanged::class]);

    $this->actingAs($this->customer)->getJson('/api/v1/customer/support/availability')->assertOk();

    Event::assertNotDispatched(SupportDeskAvailabilityChanged::class);
});
