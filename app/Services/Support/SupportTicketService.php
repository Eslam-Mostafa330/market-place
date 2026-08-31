<?php

namespace App\Services\Support;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use App\Models\Order;
use App\Models\SupportAgentStatus;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SupportTicketService
{
    public function __construct(private readonly SupportMessageService $messageService) {}

    /**
     * Open a ticket together with the message that started it.
     */
    public function openTicket(User $requester, array $data): SupportTicket
    {
        $this->ensureWithinOpenTicketLimit($requester);

        $orderId = $this->resolveOrderId($requester, $data['order_id'] ?? null);

        return DB::transaction(function () use ($requester, $data, $orderId) {
            $ticket = SupportTicket::create([
                'requester_id' => $requester->id,
                'order_id'     => $orderId,
                'subject'      => $data['subject'],
                'category'     => TicketCategory::from((int) $data['category']),
                'status'       => TicketStatus::default(),
            ]);

            $this->messageService->post($ticket, $requester, $data['message']);

            return $ticket->refresh();
        });
    }

    /**
     * Put a ticket in an agent's hands.
     *
     * Claiming is first come, first served.
     */
    public function claim(SupportTicket $ticket, User $agent): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $agent) {
            $ticket = $this->lockTicket($ticket);

            if ($ticket->isClosed()) {
                throw new UnprocessableEntityHttpException(__('support.ticket_closed'));
            }

            if ($ticket->agent_id !== null && ! $ticket->isHandledBy($agent) && ! $agent->isAdmin()) {
                throw new ConflictHttpException(__('support.already_claimed'));
            }

            $ticket->update([
                'agent_id' => $agent->id,
                'status'   => $ticket->status === TicketStatus::OPEN ? TicketStatus::ASSIGNED : $ticket->status,
            ]);

            return $ticket;
        });
    }

    /**
     * Hand a ticket back to the queue.
     *
     * An agent who cannot carry a ticket through hands it to whoever can.
     */
    public function release(SupportTicket $ticket): SupportTicket
    {
        return DB::transaction(function () use ($ticket) {
            $ticket = $this->lockTicket($ticket);

            if ($ticket->agent_id === null) {
                return $ticket;
            }

            if (! $ticket->status->canTransitionTo(TicketStatus::OPEN)) {
                throw new UnprocessableEntityHttpException(__('support.invalid_status_transition'));
            }

            $ticket->update([
                'agent_id' => null,
                'status'   => TicketStatus::OPEN,
            ]);

            return $ticket;
        });
    }

    /**
     * Move a ticket to another status, refusing anything the lifecycle forbids.
     */
    public function changeStatus(SupportTicket $ticket, TicketStatus $status): SupportTicket
    {
        return DB::transaction(function () use ($ticket, $status) {
            $ticket = $this->lockTicket($ticket);

            if ($ticket->status === $status) {
                return $ticket;
            }

            if (! $ticket->status->canTransitionTo($status)) {
                throw new UnprocessableEntityHttpException(__('support.invalid_status_transition'));
            }

            $ticket->update([
                'status'    => $status,
                'closed_at' => $status === TicketStatus::CLOSED ? now() : $ticket->closed_at,
            ]);

            return $ticket;
        });
    }

    /**
     * Close resolved tickets the customer never came back to.
     *
     * @return int The number of tickets closed.
     */
    public function closeStaleResolvedTickets(): int
    {
        return SupportTicket::where('status', TicketStatus::RESOLVED)
            ->where('updated_at', '<=', now()->subHours(config('support.auto_close_resolved_after_hours')))
            ->get()
            ->each(fn (SupportTicket $ticket) => $this->changeStatus($ticket, TicketStatus::CLOSED))
            ->count();
    }

    /**
     * Resolve tickets where the customer has stopped responding.
     *
     * Only resolve tickets that are awaiting a customer reply.
     *
     * @return int The number of tickets resolved.
     */
    public function resolveAbandonedConversations(): int
    {
        return SupportTicket::where('status', TicketStatus::ASSIGNED)
            ->where('awaiting_customer', true)
            ->where('last_message_at', '<=', now()->subMinutes(config('support.abandoned_after_minutes')))
            ->get()
            ->each(fn (SupportTicket $ticket) => $this->changeStatus($ticket, TicketStatus::RESOLVED))
            ->count();
    }

    /**
     * Return tickets assigned to agents who are no longer active.
     *
     * @return int The number of tickets returned to the queue.
     */
    public function releaseTicketsFromAbsentAgents(): int
    {
        $presentAgents = SupportAgentStatus::query()->present()->pluck('user_id');

        return SupportTicket::where('status', TicketStatus::ASSIGNED)
            ->whereNotNull('agent_id')
            ->whereNotIn('agent_id', $presentAgents)
            ->where('updated_at', '<=', now()->subMinutes(config('support.agent_presence_ttl_minutes')))
            ->get()
            ->each(fn (SupportTicket $ticket) => $this->release($ticket))
            ->count();
    }

    /**
     * Re-read the ticket under a row lock to ensure no other agent is claiming it at the same time.
     */
    private function lockTicket(SupportTicket $ticket): SupportTicket
    {
        return SupportTicket::where('id', $ticket->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * Keep one account from filling the queue on its own.
     */
    private function ensureWithinOpenTicketLimit(User $requester): void
    {
        $openTickets = SupportTicket::where('requester_id', $requester->id)
            ->active()
            ->count();

        if ($openTickets >= config('support.max_open_tickets_per_customer')) {
            throw new UnprocessableEntityHttpException(__('support.too_many_open_tickets'));
        }
    }

    /**
     * Resolve the order a ticket refers to, if any, and ensure the requester owns it.
     */
    private function resolveOrderId(User $requester, ?string $orderId): ?string
    {
        if ($orderId === null) {
            return null;
        }

        $belongsToRequester = Order::where('id', $orderId)
            ->where('customer_id', $requester->id)
            ->exists();

        if (! $belongsToRequester) {
            throw new NotFoundHttpException();
        }

        return $orderId;
    }
}
