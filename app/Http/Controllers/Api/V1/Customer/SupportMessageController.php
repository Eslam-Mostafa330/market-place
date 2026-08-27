<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Customer\Concerns\SupportTicketAuthorization;
use App\Http\Requests\Customer\Support\StoreMessageRequest;
use App\Http\Resources\Customer\Support\MessageResource;
use App\Models\SupportTicket;
use App\Services\Support\SupportMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupportMessageController extends BaseApiController
{
    use SupportTicketAuthorization;

    public function __construct(private readonly SupportMessageService $supportMessageService) {}

    public function index(SupportTicket $ticket): AnonymousResourceCollection
    {
        $this->authorizeTicket($ticket);

        $messages = $ticket->messages()
            ->select('id', 'sender_id', 'body', 'read_at', 'created_at')
            ->latest()
            ->orderByDesc('id')
            ->cursorPaginate(config('support.messages_per_page'));

        return MessageResource::collection($messages);
    }

    public function store(StoreMessageRequest $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorizeTicket($ticket);

        $message = $this->supportMessageService->post(
            ticket: $ticket,
            sender: $request->user(),
            body: $request->validated('body'),
        );

        return $this->apiResponseStored(new MessageResource($message));
    }

    /**
     * Mark everything the agent has written on this ticket as read.
     */
    public function markRead(SupportTicket $ticket): JsonResponse
    {
        $this->authorizeTicket($ticket);

        $readCount = $this->supportMessageService->markRead($ticket, auth()->user());

        return $this->apiResponseUpdated(['read_count' => $readCount]);
    }
}
