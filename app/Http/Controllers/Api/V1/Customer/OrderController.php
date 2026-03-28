<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Customer\Order\PlaceOrderRequest;
use App\Http\Resources\Customer\Order\OrderResource;
use App\Services\Order\PlaceOrderService;

class OrderController extends BaseApiController
{
    public function __construct(private readonly PlaceOrderService $placeOrderService) {}

    public function placeOrder(PlaceOrderRequest $request)
    {
        $orderData = $request->validated();
        $order = $this->placeOrderService->handle($orderData);
        $order->setRelation('delivery', $order);
        return $this->apiResponseStored(new OrderResource($order->load('items')));
    }
}
