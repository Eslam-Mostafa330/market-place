<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Filters\CustomerSupportTicketFilters;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Customer\Concerns\SupportTicketAuthorization;
use App\Http\Requests\Customer\Support\StoreTicketRequest;
use App\Http\Resources\Customer\Support\TicketResource;
use App\Models\SupportTicket;
use App\Services\Support\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SupportTicketController extends BaseApiController
{
    use SupportTicketAuthorization;

    public function __construct(private readonly SupportTicketService $supportTicketService) {}

    public function index(): AnonymousResourceCollection
    {
        $customerId = auth()->id();

        $tickets = SupportTicket::where('requester_id', $customerId)
            ->with('agent:id,name')
            ->withCount(['messages as unread_count' => fn ($query) => $query->unreadFor($customerId)])
            ->useFilters(CustomerSupportTicketFilters::class)
            ->latest('last_message_at')
            ->dynamicPaginate();

        return TicketResource::collection($tickets);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->supportTicketService->openTicket(
            requester: $request->user(),
            data: $request->validated(),
        );

        return $this->apiResponseStored(new TicketResource($ticket));
    }

    public function show(SupportTicket $ticket): JsonResponse
    {
        $this->authorizeTicket($ticket);

        $ticket->load('agent:id,name');

        return $this->apiResponseShow(new TicketResource($ticket));
    }
}
