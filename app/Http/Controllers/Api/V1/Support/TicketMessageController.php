<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Support\Concerns\TicketHandlingAuthorization;
use App\Http\Requests\Support\Ticket\StoreMessageRequest;
use App\Http\Resources\Support\Ticket\MessageResource;
use App\Models\SupportTicket;
use App\Services\Support\SupportMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketMessageController extends BaseApiController
{
    use TicketHandlingAuthorization;

    public function __construct(private readonly SupportMessageService $supportMessageService) {}

    public function index(SupportTicket $ticket): AnonymousResourceCollection
    {
        $messages = $ticket->messages()
            ->select('id', 'sender_id', 'body', 'read_at', 'created_at')
            ->with('sender:id,name,role')
            ->latest()
            ->orderByDesc('id')
            ->cursorPaginate(config('support.messages_per_page'));

        return MessageResource::collection($messages);
    }

    public function store(StoreMessageRequest $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorizeTicketHandling($ticket);

        $message = $this->supportMessageService->post(
            ticket: $ticket,
            sender: $request->user(),
            body: $request->validated('body'),
        );

        return $this->apiResponseStored(new MessageResource($message->load('sender:id,name,role')));
    }

    /**
     * Mark everything the customer has written on this ticket as read.
     */
    public function markRead(SupportTicket $ticket): JsonResponse
    {
        $this->authorizeTicketHandling($ticket);

        $readCount = $this->supportMessageService->markRead($ticket, auth()->user());

        return $this->apiResponseUpdated(['read_count' => $readCount]);
    }
}
