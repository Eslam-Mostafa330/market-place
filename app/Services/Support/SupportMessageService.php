<?php

namespace App\Services\Support;

use App\Enums\TicketStatus;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SupportMessageService
{
    /**
     * Append a message to a ticket and move the ticket on accordingly.
     */
    public function post(SupportTicket $ticket, User $sender, string $body): SupportMessage
    {
        return DB::transaction(function () use ($ticket, $sender, $body) {
            $ticket = SupportTicket::where('id', $ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($ticket->isClosed()) {
                throw new UnprocessableEntityHttpException(__('support.ticket_closed'));
            }

            if ($sender->staffsSupportDesk() && ! $ticket->isHandledBy($sender)) {
                throw new HttpException(403, __('support.claim_before_replying'));
            }

            $message = SupportMessage::create([
                'ticket_id' => $ticket->id,
                'sender_id' => $sender->id,
                'body'      => $body,
            ]);

            $ticket->update($this->ticketStateAfter($ticket, $sender));

            return $message;
        });
    }

    /**
     * Mark everything the reader did not write as read.
     *
     * @return int The number of messages that were still unread.
     */
    public function markRead(SupportTicket $ticket, User $reader): int
    {
        return $ticket->messages()
            ->unreadFor($reader->id)
            ->update(['read_at' => now()]);
    }

    /**
     * The ticket columns a new message changes.
     */
    private function ticketStateAfter(SupportTicket $ticket, User $sender): array
    {
        $attributes = ['last_message_at' => now()];

        if ($sender->staffsSupportDesk()) {
            return $attributes + [
                'first_replied_at' => $ticket->first_replied_at ?? now(),
                'status'           => TicketStatus::ASSIGNED,
            ];
        }

        if ($ticket->status === TicketStatus::RESOLVED) {
            $attributes['status'] = TicketStatus::ASSIGNED;
        }

        return $attributes;
    }
}
