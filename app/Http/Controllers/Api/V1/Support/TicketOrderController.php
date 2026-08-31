<?php

namespace App\Http\Controllers\Api\V1\Support;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Support\Order\OrderResource;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;

class TicketOrderController extends BaseApiController
{
    /**
     * Return the order associated with the ticket.
     */
    public function __invoke(SupportTicket $ticket): JsonResponse
    {
        abort_if($ticket->order_id === null, 404);

        $ticket->load('order.items');

        return $this->apiResponseShow(new OrderResource($ticket->order));
    }
}
