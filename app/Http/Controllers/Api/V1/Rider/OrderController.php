<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Rider\Concerns\RiderOrderAuthorization;
use App\Http\Resources\Rider\Order\OrderDeliverResource;
use App\Http\Resources\Rider\Order\OrderResource;
use App\Models\Order;
use App\Services\Order\RiderOrderService;

class OrderController extends BaseApiController
{
    use RiderOrderAuthorization;

    public function __construct(private readonly RiderOrderService $riderOrderService) {}

    /**
     * Rider rejects the order, triggers automatic re-search.
     */
    public function reject(Order $order)
    {
        $this->authorizeRiderOrder($order);
        $order = $this->riderOrderService->rejectOrder($order);
        return $this->apiResponse(new OrderResource($order));
    }

    /**
     * Rider confirms pickup.
     */
    public function pickup(Order $order)
    {
        $this->authorizeRiderOrder($order);
        $order = $this->riderOrderService->pickupOrder($order);
        return $this->apiResponse(new OrderResource($order));
    }

    /**
     * Rider marks order as delivered.
     */
    public function deliver(Order $order)
    {
        $this->authorizeRiderOrder($order);
        $order = $this->riderOrderService->deliverOrder($order);
        return $this->apiResponse(new OrderDeliverResource($order));
    }
}