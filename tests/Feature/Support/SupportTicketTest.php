<?php

use App\Enums\AgentAvailability;
use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Models\Order;
use App\Models\SupportAgentStatus;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Support\SupportMessageService;
use App\Services\Support\SupportPresenceService;
use App\Services\Support\SupportTicketService;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Mail::fake();

    $this->customer = User::factory()->customer()->create();
    $this->agent    = User::factory()->support()->create();
});

/**
 * Put an agent on the desk, the way their console does on every heartbeat.
 */
function goOnline(User $agent, AgentAvailability $availability = AgentAvailability::ONLINE): void
{
    app(SupportPresenceService::class)->heartbeat($agent, $availability);
}

/**
 * Opening a ticket
 */
it('opens a ticket carrying its first message', function () {
    $this->actingAs($this->customer);

    $response = $this->postJson('/api/v1/customer/support/tickets', [
        'subject'  => 'My order never arrived',
        'message'  => 'I have been waiting for two hours.',
        'category' => TicketCategory::DELIVERY->value,
    ])->assertCreated();

    $ticket = SupportTicket::findOrFail($response->json('data.id'));

    expect($response->json('data.last_message_at'))->not->toBeNull()
        ->and($ticket->requester_id)->toBe($this->customer->id)
        ->and($ticket->status)->toBe(TicketStatus::OPEN)
        ->and($ticket->agent_id)->toBeNull()
        ->and($ticket->last_message_at)->not->toBeNull()
        ->and($ticket->messages()->count())->toBe(1)
        ->and($ticket->messages()->first()->sender_id)->toBe($this->customer->id);
});

it('rejects a ticket with no message or subject', function () {
    $this->actingAs($this->customer);

    $this->postJson('/api/v1/customer/support/tickets', [])
        ->assertApiValidationErrors(['subject', 'message', 'category']);
});

it('stops a customer opening more tickets than the limit allows', function () {
    $this->actingAs($this->customer);

    SupportTicket::factory()
        ->count(config('support.max_open_tickets_per_customer'))
        ->create(['requester_id' => $this->customer->id]);

    $this->postJson('/api/v1/customer/support/tickets', [
        'subject'  => 'One too many',
        'message'  => 'Please help.',
        'category' => TicketCategory::OTHER->value,
    ])->assertStatus(422);
});

it('counts only live tickets against the limit', function () {
    $this->actingAs($this->customer);

    SupportTicket::factory()
        ->count(config('support.max_open_tickets_per_customer'))
        ->closed()
        ->create(['requester_id' => $this->customer->id]);

    $this->postJson('/api/v1/customer/support/tickets', [
        'subject'  => 'A new problem',
        'message'  => 'Please help.',
        'category' => TicketCategory::OTHER->value,
    ])->assertCreated();
});

it('makes an order category name its order', function () {
    $this->actingAs($this->customer);

    $this->postJson('/api/v1/customer/support/tickets', [
        'subject'  => 'Something went wrong',
        'message'  => 'Please help.',
        'category' => TicketCategory::ORDER->value,
    ])->assertApiValidationErrors(['order_id']);

    expect(SupportTicket::count())->toBe(0);
});

it('accepts an order category that names an order the customer owns', function () {
    $this->actingAs($this->customer);

    $order = Order::factory()->create(['customer_id' => $this->customer->id]);

    $response = $this->postJson('/api/v1/customer/support/tickets', [
        'subject'  => 'My order never arrived',
        'message'  => 'Please help.',
        'category' => TicketCategory::ORDER->value,
        'order_id' => $order->id,
    ])->assertCreated();

    expect(SupportTicket::findOrFail($response->json('data.id'))->order_id)->toBe($order->id);
});

it('leaves the order optional for every other category', function () {
    $this->actingAs($this->customer);

    foreach ([TicketCategory::ACCOUNT, TicketCategory::DELIVERY, TicketCategory::OTHER] as $category) {
        $this->postJson('/api/v1/customer/support/tickets', [
            'subject'  => 'I need a hand with something',
            'message'  => 'Please help.',
            'category' => $category->value,
        ])->assertCreated();
    }
});

it('refuses to attach an order belonging to someone else', function () {
    $this->actingAs($this->customer);

    $foreignOrder = Order::factory()->create();

    $this->postJson('/api/v1/customer/support/tickets', [
        'subject'  => 'About this order',
        'message'  => 'Something is wrong.',
        'category' => TicketCategory::ORDER->value,
        'order_id' => $foreignOrder->id,
    ])->assertNotFound();

    expect(SupportTicket::count())->toBe(0);
});

/**
 * Reading a ticket
 */
it('lists only the tickets the customer opened', function () {
    $mine    = SupportTicket::factory()->create(['requester_id' => $this->customer->id]);
    $foreign = SupportTicket::factory()->create();

    $this->actingAs($this->customer);

    $ids = collect($this->getJson('/api/v1/customer/support/tickets')->assertOk()->json('data'))
        ->pluck('id');

    expect($ids)->toContain($mine->id)
        ->and($ids)->not->toContain($foreign->id);
});

it('hides another customer ticket behind a 404', function () {
    $foreign = SupportTicket::factory()->create();

    $this->actingAs($this->customer);

    $this->getJson("/api/v1/customer/support/tickets/{$foreign->id}")->assertNotFound();
    $this->postJson("/api/v1/customer/support/tickets/{$foreign->id}/messages", ['body' => 'hello'])->assertNotFound();
});

it('reports how many agent messages the customer has not read', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    SupportMessage::factory()->count(2)->create(['ticket_id' => $ticket->id, 'sender_id' => $this->agent->id]);
    SupportMessage::factory()->create(['ticket_id' => $ticket->id, 'sender_id' => $this->customer->id]);

    $this->actingAs($this->customer);

    $response = $this->getJson('/api/v1/customer/support/tickets')->assertOk();

    expect($response->json('data.0.unread_count'))->toBe(2);
});

it('marks the agent messages read and leaves the customer own alone', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    SupportMessage::factory()->create(['ticket_id' => $ticket->id, 'sender_id' => $this->agent->id]);
    $ownMessage = SupportMessage::factory()->create(['ticket_id' => $ticket->id, 'sender_id' => $this->customer->id]);

    $this->actingAs($this->customer);

    $this->postJson("/api/v1/customer/support/tickets/{$ticket->id}/read")
        ->assertOk()
        ->assertJsonPath('data.read_count', 1);

    expect($ownMessage->fresh()->read_at)->toBeNull();
});

/**
 * The conversation
 */
it('refuses new messages on a closed ticket', function () {
    $ticket = SupportTicket::factory()->closed()->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->customer);

    $this->postJson("/api/v1/customer/support/tickets/{$ticket->id}/messages", ['body' => 'Still broken'])
        ->assertStatus(422);

    expect($ticket->messages()->count())->toBe(0);
});

it('reopens a resolved ticket when the customer writes again', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->resolved()->create([
        'requester_id' => $this->customer->id,
    ]);

    $this->actingAs($this->customer);

    $this->postJson("/api/v1/customer/support/tickets/{$ticket->id}/messages", ['body' => 'It happened again'])
        ->assertCreated();

    expect($ticket->fresh()->status)->toBe(TicketStatus::ASSIGNED);
});

it('refuses to let an agent write in a ticket they have not claimed', function () {
    $ticket = SupportTicket::factory()->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $this->postJson("/api/v1/support/tickets/{$ticket->id}/messages", ['body' => 'Looking into it now'])
        ->assertForbidden();

    $ticket->refresh();

    expect($ticket->messages()->count())->toBe(0)
        ->and($ticket->agent_id)->toBeNull()
        ->and($ticket->status)->toBe(TicketStatus::OPEN);
});

it('lets an agent write once the ticket is theirs', function () {
    $ticket = SupportTicket::factory()->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $this->postJson("/api/v1/support/tickets/{$ticket->id}/claim")->assertOk();

    $this->postJson("/api/v1/support/tickets/{$ticket->id}/messages", ['body' => 'Looking into it now'])
        ->assertCreated();

    $ticket->refresh();

    expect($ticket->agent_id)->toBe($this->agent->id)
        ->and($ticket->status)->toBe(TicketStatus::ASSIGNED)
        ->and($ticket->first_replied_at)->not->toBeNull();
});

/**
 * The controller checks ownership before the transaction, so two agents reading a
 * free ticket at the same moment can both reach the service. The write itself has
 * to be the thing that refuses, which is why the guard sits inside the lock.
 */
it('stops a second agent writing into a conversation that was free a moment ago', function () {
    $otherAgent = User::factory()->support()->create();
    $ticket     = SupportTicket::factory()->create(['requester_id' => $this->customer->id]);

    app(SupportTicketService::class)->claim($ticket, $this->agent);

    expect(fn () => app(SupportMessageService::class)->post($ticket, $otherAgent, 'Butting in'))
        ->toThrow(HttpException::class);

    expect($ticket->fresh()->messages()->count())->toBe(0);
});

it('keeps the first reply timestamp on later replies', function () {
    $repliedAt = now()->subHour();

    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create([
        'requester_id'     => $this->customer->id,
        'first_replied_at' => $repliedAt,
    ]);

    $this->actingAs($this->agent);

    $this->postJson("/api/v1/support/tickets/{$ticket->id}/messages", ['body' => 'Any update for you'])
        ->assertCreated();

    expect($ticket->fresh()->first_replied_at->timestamp)->toBe($repliedAt->timestamp);
});

/**
 * The agent desk
 */
it('puts an unclaimed ticket in the agent hands', function () {
    $ticket = SupportTicket::factory()->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $this->postJson("/api/v1/support/tickets/{$ticket->id}/claim")->assertOk();

    $ticket->refresh();

    expect($ticket->agent_id)->toBe($this->agent->id)
        ->and($ticket->status)->toBe(TicketStatus::ASSIGNED);
});

it('refuses a ticket another agent already claimed', function () {
    $otherAgent = User::factory()->support()->create();
    $ticket     = SupportTicket::factory()->assignedTo($otherAgent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $this->postJson("/api/v1/support/tickets/{$ticket->id}/claim")->assertStatus(409);

    expect($ticket->fresh()->agent_id)->toBe($otherAgent->id);
});

it('hands a ticket back to the queue', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $this->deleteJson("/api/v1/support/tickets/{$ticket->id}/claim")->assertOk();

    $ticket->refresh();

    expect($ticket->agent_id)->toBeNull()
        ->and($ticket->status)->toBe(TicketStatus::OPEN);
});

it('lets another agent take a released ticket', function () {
    $otherAgent = User::factory()->support()->create();
    $ticket     = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    app(SupportTicketService::class)->release($ticket);

    $this->actingAs($otherAgent);

    $this->postJson("/api/v1/support/tickets/{$ticket->id}/claim")->assertOk();

    expect($ticket->fresh()->agent_id)->toBe($otherAgent->id);
});

it('refuses to release a ticket another agent is holding', function () {
    $otherAgent = User::factory()->support()->create();
    $ticket     = SupportTicket::factory()->assignedTo($otherAgent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $this->deleteJson("/api/v1/support/tickets/{$ticket->id}/claim")->assertForbidden();

    expect($ticket->fresh()->agent_id)->toBe($otherAgent->id);
});

it('refuses to release a ticket that was already resolved', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->resolved()->create([
        'requester_id' => $this->customer->id,
    ]);

    $this->actingAs($this->agent);

    $this->deleteJson("/api/v1/support/tickets/{$ticket->id}/claim")->assertStatus(422);

    expect($ticket->fresh()->agent_id)->toBe($this->agent->id);
});

it('shrugs at releasing a ticket nobody was holding', function () {
    $ticket = SupportTicket::factory()->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $this->deleteJson("/api/v1/support/tickets/{$ticket->id}/claim")->assertOk();

    expect($ticket->fresh()->agent_id)->toBeNull();
});

it('keeps an agent out of a conversation another agent is having', function () {
    $otherAgent = User::factory()->support()->create();
    $ticket     = SupportTicket::factory()->assignedTo($otherAgent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $this->postJson("/api/v1/support/tickets/{$ticket->id}/messages", ['body' => 'Butting in'])
        ->assertForbidden();
});

it('lets an admin take over a claimed ticket', function () {
    $admin  = User::factory()->admin()->create();
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($admin);

    $this->postJson("/api/v1/support/tickets/{$ticket->id}/claim")->assertOk();

    expect($ticket->fresh()->agent_id)->toBe($admin->id);
});

it('moves a ticket through resolved and closed', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $this->patchJson("/api/v1/support/tickets/{$ticket->id}/status", ['status' => TicketStatus::RESOLVED->value])->assertOk();

    expect($ticket->fresh()->status)->toBe(TicketStatus::RESOLVED);

    $this->patchJson("/api/v1/support/tickets/{$ticket->id}/status", ['status' => TicketStatus::CLOSED->value])->assertOk();

    $ticket->refresh();

    expect($ticket->status)->toBe(TicketStatus::CLOSED)
        ->and($ticket->closed_at)->not->toBeNull();
});

it('refuses a status the lifecycle does not allow', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->closed()->create([
        'requester_id' => $this->customer->id,
    ]);

    $this->actingAs($this->agent);

    $this->patchJson("/api/v1/support/tickets/{$ticket->id}/status", ['status' => TicketStatus::RESOLVED->value])
        ->assertStatus(422);
});

it('refuses to set a status that should follow from an action', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->resolved()->create([
        'requester_id' => $this->customer->id,
    ]);

    $this->actingAs($this->agent);

    foreach ([TicketStatus::OPEN, TicketStatus::ASSIGNED] as $status) {
        $this->patchJson("/api/v1/support/tickets/{$ticket->id}/status", ['status' => $status->value])
            ->assertApiValidationErrors(['status']);
    }

    expect($ticket->fresh()->status)->toBe(TicketStatus::RESOLVED);
});

it('leaves closing to the desk rather than the customer', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->customer);

    $this->postJson("/api/v1/customer/support/tickets/{$ticket->id}/close")->assertNotFound();

    expect($ticket->fresh()->status)->toBe(TicketStatus::ASSIGNED);
});

it('closes a resolved ticket the customer never came back to', function () {
    $stale = SupportTicket::factory()->assignedTo($this->agent)->resolved()->create([
        'requester_id' => $this->customer->id,
    ]);

    $this->travel(config('support.auto_close_resolved_after_hours') + 1)->hours();

    $this->artisan('support:sweep-tickets')->assertSuccessful();

    $stale->refresh();

    expect($stale->status)->toBe(TicketStatus::CLOSED)
        ->and($stale->closed_at)->not->toBeNull();
});

it('leaves a freshly resolved ticket open for the customer to return to', function () {
    $recent = SupportTicket::factory()->assignedTo($this->agent)->resolved()->create([
        'requester_id' => $this->customer->id,
    ]);

    $this->artisan('support:sweep-tickets')->assertSuccessful();

    expect($recent->fresh()->status)->toBe(TicketStatus::RESOLVED);
});

it('never sweeps away a ticket the desk still owes an answer', function () {
    $waiting  = SupportTicket::factory()->create(['requester_id' => $this->customer->id]);
    $ongoing  = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->travel(config('support.auto_close_resolved_after_hours') + 100)->hours();

    $this->artisan('support:sweep-tickets')->assertSuccessful();

    expect($waiting->fresh()->status)->toBe(TicketStatus::OPEN)
        ->and($ongoing->fresh()->status)->not->toBe(TicketStatus::CLOSED);
});

it('caps a long conversation instead of sending all of it', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    SupportMessage::factory()->count(40)->create([
        'ticket_id' => $ticket->id,
        'sender_id' => $this->agent->id,
    ]);

    $this->actingAs($this->agent);

    $perPage = config('support.messages_per_page');

    $response = $this->getJson("/api/v1/support/tickets/{$ticket->id}/messages")->assertOk();

    expect($response->json('data'))->toHaveCount($perPage)
        ->and($response->json('meta.next_cursor'))->not->toBeNull();

    $flood = $this->getJson("/api/v1/support/tickets/{$ticket->id}/messages?pagination=none&per_page=1000")->assertOk();

    expect($flood->json('data'))->toHaveCount($perPage);
});

it('walks a whole conversation by cursor without losing or repeating a message', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $written = SupportMessage::factory()->count(70)->create([
        'ticket_id' => $ticket->id,
        'sender_id' => $this->agent->id,
    ])->pluck('id');

    $this->actingAs($this->agent);

    $seen   = collect();
    $cursor = null;

    do {
        $url      = "/api/v1/support/tickets/{$ticket->id}/messages".($cursor ? "?cursor={$cursor}" : '');
        $response = $this->getJson($url)->assertOk();

        $seen   = $seen->merge(collect($response->json('data'))->pluck('id'));
        $cursor = $response->json('meta.next_cursor');
    } while ($cursor);

    expect($seen)->toHaveCount(70)
        ->and($seen->unique())->toHaveCount(70)
        ->and($seen->sort()->values())->toEqual($written->sort()->values());
});

it('does not repeat a message when a new one arrives mid scroll', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    SupportMessage::factory()->count(40)->create([
        'ticket_id' => $ticket->id,
        'sender_id' => $this->agent->id,
    ]);

    $this->actingAs($this->agent);

    $first  = $this->getJson("/api/v1/support/tickets/{$ticket->id}/messages")->assertOk();
    $cursor = $first->json('meta.next_cursor');

    SupportMessage::factory()->create(['ticket_id' => $ticket->id, 'sender_id' => $this->customer->id]);

    $second = $this->getJson("/api/v1/support/tickets/{$ticket->id}/messages?cursor={$cursor}")->assertOk();

    $firstIds  = collect($first->json('data'))->pluck('id');
    $secondIds = collect($second->json('data'))->pluck('id');

    expect($firstIds->intersect($secondIds))->toBeEmpty();
});

it('sends a message with what a chat bubble needs and nothing else', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    SupportMessage::factory()->create(['ticket_id' => $ticket->id, 'sender_id' => $this->agent->id]);

    $this->actingAs($this->agent);

    $response = $this->getJson("/api/v1/support/tickets/{$ticket->id}/messages")->assertOk();

    expect(array_keys($response->json('data.0')))->toBe(['id', 'body', 'sender_name', 'from_desk', 'read_at', 'created_at'])
        ->and($response->json('data.0.from_desk'))->toBeTrue()
        ->and($response->json('data.0.created_at'))->not->toBeNull();
});

/**
 * What the desk endpoints hand back
 */
it('keeps the agent queue list lean', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    SupportMessage::factory()->create(['ticket_id' => $ticket->id, 'sender_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $row = $this->getJson('/api/v1/support/tickets')->assertOk()->json('data.0');

    expect(array_keys($row))->toBe([
        'id', 'subject', 'category', 'status',
        'agent_name', 'is_mine', 'unread_count', 'last_message_at',
    ])
        ->and($row['is_mine'])->toBeTrue()
        ->and($row['unread_count'])->toBe(1);
});

it('keeps the customer ticket list lean', function () {
    SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->customer);

    $row = $this->getJson('/api/v1/customer/support/tickets')->assertOk()->json('data.0');

    expect(array_keys($row))->toBe(['id', 'subject', 'category', 'status', 'unread_count', 'last_message_at']);
});

it('marks a ticket held by someone else as not mine', function () {
    $otherAgent = User::factory()->support()->create();

    SupportTicket::factory()->assignedTo($otherAgent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $row = $this->getJson('/api/v1/support/tickets')->assertOk()->json('data.0');

    expect($row['is_mine'])->toBeFalse()
        ->and($row['agent_name'])->toBe($otherAgent->name);
});

it('answers a claim with the assignment alone', function () {
    $ticket = SupportTicket::factory()->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $response = $this->postJson("/api/v1/support/tickets/{$ticket->id}/claim")->assertOk();

    expect(array_keys($response->json('data')))->toBe(['id', 'status', 'agent_name', 'is_mine', 'closed_at'])
        ->and($response->json('data.agent_name'))->toBe($this->agent->name)
        ->and($response->json('data.is_mine'))->toBeTrue();
});

it('answers a release with an empty assignment', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $response = $this->deleteJson("/api/v1/support/tickets/{$ticket->id}/claim")->assertOk();

    expect(array_keys($response->json('data')))->toBe(['id', 'status', 'agent_name', 'is_mine', 'closed_at'])
        ->and($response->json('data.agent_name'))->toBeNull()
        ->and($response->json('data.is_mine'))->toBeFalse();
});

it('answers a status change with where the ticket landed', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $response = $this->patchJson("/api/v1/support/tickets/{$ticket->id}/status", [
        'status' => TicketStatus::CLOSED->value,
    ])->assertOk();

    expect(array_keys($response->json('data')))->toBe(['id', 'status', 'agent_name', 'is_mine', 'closed_at'])
        ->and($response->json('data.closed_at'))->not->toBeNull();
});

it('shows the requester without handing out their id', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $response = $this->getJson("/api/v1/support/tickets/{$ticket->id}")->assertOk();

    expect(array_keys($response->json('data.requester')))->toBe(['name', 'email'])
        ->and($response->json('data.requester.email'))->toBe($this->customer->email)
        ->and(array_keys($response->json('data.agent')))->toBe(['id', 'name']);
});

/**
 * Presence
 */
it('reports an agent as offline before they ever check in', function () {
    $this->actingAs($this->agent);

    $this->getJson('/api/v1/support/availability')
        ->assertOk()
        ->assertJsonPath('data.availability', AgentAvailability::OFFLINE->value);

    expect(SupportAgentStatus::where('user_id', $this->agent->id)->exists())->toBeFalse();
});

it('records an agent arriving at their console', function () {
    $this->actingAs($this->agent);

    $this->patchJson('/api/v1/support/availability', ['availability' => AgentAvailability::ONLINE->value])
        ->assertOk();

    $status = SupportAgentStatus::where('user_id', $this->agent->id)->firstOrFail();

    expect($status->availability)->toBe(AgentAvailability::ONLINE)
        ->and($status->last_seen_at)->not->toBeNull();
});

it('tells the customer the desk is staffed without naming anyone', function () {
    goOnline($this->agent);

    $this->actingAs($this->customer);

    $response = $this->getJson('/api/v1/customer/support/availability')->assertOk();
    $payload  = json_encode($response->json());

    expect($response->json('data.support_available'))->toBeTrue()
        ->and($response->json('data'))->toHaveKeys(['support_available', 'message'])
        ->and($payload)->not->toContain($this->agent->name)
        ->and($payload)->not->toContain($this->agent->id);
});

it('treats a stale heartbeat as nobody being there', function () {
    goOnline($this->agent);

    $this->travel(config('support.agent_presence_ttl_minutes') + 1)->minutes();

    $this->actingAs($this->customer);

    expect($this->getJson('/api/v1/customer/support/availability')->json('data.support_available'))->toBeFalse();
});

it('reports an unstaffed desk when every agent is offline', function () {
    goOnline($this->agent, AgentAvailability::OFFLINE);

    $this->actingAs($this->customer);

    expect($this->getJson('/api/v1/customer/support/availability')->json('data.support_available'))->toBeFalse();
});

/**
 * Who may reach which desk
 */
it('hides the agent desk from a customer', function () {
    $this->actingAs($this->customer);

    $this->getJson('/api/v1/support/tickets')->assertNotFound();
});

it('hides the customer side from an agent', function () {
    $this->actingAs($this->agent);

    $this->getJson('/api/v1/customer/support/tickets')->assertNotFound();
});

it('lets an admin work the agent desk', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $this->getJson('/api/v1/support/tickets')->assertOk();
});

it('turns away an agent who is not logged in', function () {
    $this->getJson('/api/v1/support/tickets')->assertUnauthorized();
});

/**
 * The requester's orders, beside the ticket
 */
it('shows the desk the order the ticket is about', function () {
    $order  = Order::factory()->create(['customer_id' => $this->customer->id]);
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create([
        'requester_id' => $this->customer->id,
        'order_id'     => $order->id,
    ]);

    $this->actingAs($this->agent);

    $response = $this->getJson("/api/v1/support/tickets/{$ticket->id}/order")->assertOk();

    expect($response->json('data.order_number'))->toBe($order->order_number)
        ->and($response->json('data'))->toHaveKeys(['order_status', 'payment_status', 'total', 'placed_at', 'items']);
});

it('has no order to show for a ticket that names none', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->agent);

    $this->getJson("/api/v1/support/tickets/{$ticket->id}/order")->assertNotFound();
});

it('keeps a customer away from the desk order lookup', function () {
    $ticket = SupportTicket::factory()->create(['requester_id' => $this->customer->id]);

    $this->actingAs($this->customer);

    $this->getJson("/api/v1/support/tickets/{$ticket->id}/order")->assertNotFound();
});

/**
 * Conversations nobody came back to
 */
it('resolves a conversation the customer walked away from', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    app(SupportMessageService::class)->post($ticket, $this->agent, 'Anything else I can help with?');

    $this->travel(config('support.abandoned_after_minutes') + 5)->minutes();

    $this->artisan('support:sweep-tickets')->assertSuccessful();

    expect($ticket->fresh()->status)->toBe(TicketStatus::RESOLVED);
});

it('never abandons a customer who is waiting for an answer', function () {
    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    app(SupportMessageService::class)->post($ticket, $this->agent, 'Let me check that');
    app(SupportMessageService::class)->post($ticket, $this->customer, 'Any update?');

    $this->travel(config('support.abandoned_after_minutes') + 5)->minutes();

    $this->artisan('support:sweep-tickets')->assertSuccessful();

    expect($ticket->fresh()->status)->not->toBe(TicketStatus::RESOLVED)
        ->and($ticket->fresh()->awaiting_customer)->toBeFalse();
});

it('hands back the tickets of an agent who disappeared', function () {
    goOnline($this->agent);

    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->travel(config('support.agent_presence_ttl_minutes') + 5)->minutes();

    $this->artisan('support:sweep-tickets')->assertSuccessful();

    $ticket->refresh();

    expect($ticket->agent_id)->toBeNull()
        ->and($ticket->status)->toBe(TicketStatus::OPEN);
});

it('leaves the tickets of an agent who is still there', function () {
    goOnline($this->agent);

    $ticket = SupportTicket::factory()->assignedTo($this->agent)->create(['requester_id' => $this->customer->id]);

    $this->artisan('support:sweep-tickets')->assertSuccessful();

    expect($ticket->fresh()->agent_id)->toBe($this->agent->id);
});

/**
 * Presence without a clock
 */
it('keeps a working agent online without any ping of its own', function () {
    goOnline($this->agent);

    $this->travel(5)->minutes();

    $this->actingAs($this->agent);
    $this->getJson('/api/v1/support/tickets')->assertOk();

    $status = SupportAgentStatus::where('user_id', $this->agent->id)->firstOrFail();

    expect($status->last_seen_at->diffInSeconds(now()))->toBeLessThan(5)
        ->and(app(SupportPresenceService::class)->deskIsStaffed())->toBeTrue();
});

it('does not sign an agent back in just because they made a request', function () {
    goOnline($this->agent, AgentAvailability::OFFLINE);

    $this->actingAs($this->agent);
    $this->getJson('/api/v1/support/tickets')->assertOk();

    expect(SupportAgentStatus::where('user_id', $this->agent->id)->firstOrFail()->availability)
        ->toBe(AgentAvailability::OFFLINE)
        ->and(app(SupportPresenceService::class)->deskIsStaffed())->toBeFalse();
});

it('lets an idle agent go quiet on their own', function () {
    goOnline($this->agent);

    $this->travel(config('support.agent_presence_ttl_minutes') + 1)->minutes();

    expect(app(SupportPresenceService::class)->deskIsStaffed())->toBeFalse();
});

it('leaves reassignment to the sweep rather than to whoever opens the queue', function () {
    $absent = User::factory()->support()->create();
    goOnline($absent);

    $ticket = SupportTicket::factory()->assignedTo($absent)->create(['requester_id' => $this->customer->id]);

    $this->travel(config('support.agent_presence_ttl_minutes') + 5)->minutes();

    $this->actingAs($this->agent);
    $this->getJson('/api/v1/support/tickets')->assertOk();

    expect($ticket->fresh()->agent_id)->toBe($absent->id);
});
