<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Enums\TicketStatus;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Support\Concerns\TicketHandlingAuthorization;
use App\Http\Requests\Support\Ticket\UpdateTicketStatusRequest;
use App\Http\Resources\Support\Ticket\TicketListResource;
use App\Http\Resources\Support\Ticket\TicketResource;
use App\Http\Resources\Support\Ticket\TicketStateResource;
use App\Models\SupportTicket;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TicketController extends BaseApiController
{
    use TicketHandlingAuthorization;

    public function __construct(private readonly SupportTicketService $supportTicketService) {}

    public function index(): AnonymousResourceCollection
    {
        $agentId = auth()->id();

        $tickets = SupportTicket::with('agent:id,name')
            ->withCount(['messages as unread_count' => fn ($query) => $query->unreadFor($agentId)])
            ->useFilters()
            ->latest('last_message_at')
            ->dynamicPaginate();

        return TicketListResource::collection($tickets);
    }

    public function show(SupportTicket $ticket): JsonResponse
    {
        $ticket->load('requester:id,name,email', 'agent:id,name');

        return $this->apiResponseShow(new TicketResource($ticket));
    }

    /**
     * Take a ticket off the queue and onto the agent's own list.
     */
    public function claim(SupportTicket $ticket): JsonResponse
    {
        $ticket = $this->supportTicketService->claim($ticket, auth()->user());

        return $this->apiResponseUpdated(new TicketStateResource($ticket->load('agent:id,name')));
    }

    /**
     * Give a ticket back to the queue for another agent to take.
     */
    public function release(SupportTicket $ticket): JsonResponse
    {
        $this->authorizeTicketHandling($ticket);

        $ticket = $this->supportTicketService->release($ticket);

        return $this->apiResponseUpdated(new TicketStateResource($ticket->load('agent:id,name')));
    }

    /**
     * Call a ticket resolved, or close it for good.
     */
    public function updateStatus(UpdateTicketStatusRequest $request, SupportTicket $ticket): JsonResponse
    {
        $this->authorizeTicketHandling($ticket);

        $ticket = $this->supportTicketService->changeStatus(
            ticket: $ticket,
            status: TicketStatus::from((int) $request->validated('status')),
        );

        return $this->apiResponseUpdated(new TicketStateResource($ticket->load('agent:id,name')));
    }
}
