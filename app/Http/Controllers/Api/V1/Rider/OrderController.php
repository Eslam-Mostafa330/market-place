<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Resources\Rider\Order\OrderDeliverResource;
use App\Http\Resources\Rider\Order\OrderResource;
use App\Services\Order\RiderOrderService;

class OrderController extends BaseApiController
{
    public function __construct(private readonly RiderOrderService $riderOrderService) {}

    /**
     * Rider rejects the order, triggers automatic re-search.
     */
    public function reject(string $orderId)
    {
        $order = $this->riderOrderService->rejectOrder($orderId, auth()->id());
        return $this->apiResponse(new OrderResource($order));
    }

    /**
     * Rider confirms pickup.
     */
    public function pickup(string $orderId)
    {
        $order = $this->riderOrderService->pickupOrder($orderId, auth()->id());
        return $this->apiResponse(new OrderResource($order));
    }

    /**
     * Rider marks order as delivered.
     */
    public function deliver(string $orderId)
    {
        $order = $this->riderOrderService->deliverOrder($orderId, auth()->id());
        return $this->apiResponse(new OrderDeliverResource($order));
    }
}