<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Order\AssignRiderRequest;
use App\Http\Requests\Admin\Order\CancelOrderRequest;
use App\Http\Resources\Admin\Order\CanceledOrderResource;
use App\Http\Resources\Admin\Order\OrderResource;
use App\Models\Order;
use App\Models\User;
use App\Services\Order\AdminOrderService;

class OrderController extends BaseApiController
{
    public function __construct(private readonly AdminOrderService $adminOrderService) {}

    /**
     * Manually assign a rider to an order.
     */
    public function assignRider(AssignRiderRequest $request, Order $order)
    {
        $data = $request->validated();
        $rider = User::findOrFail($data['rider_id']);
        $order = $this->adminOrderService->assignRider($order, $rider);
        $order->setRelations(['rider' => $rider, 'delivery' => $order]);
        return $this->apiResponseUpdated(new OrderResource($order));
    }

    /**
     * Cancel an order with an optional note.
     */
    public function cancel(CancelOrderRequest $request, Order $order)
    {
        $data = $request->validated();
        $order = $this->adminOrderService->cancelOrder($order, $data['note']);
        $order->load('customer:id,name,email');
        return $this->apiResponse(new CanceledOrderResource($order));
    }

    /**
     * Extend the automatic rider search for another 5 minutes.
     */
    public function extendSearch(Order $order)
    {
        $order = $this->adminOrderService->extendSearch($order);
        return $this->apiResponse(new OrderResource($order));
    }
}