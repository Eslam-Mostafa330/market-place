<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Order\AssignRiderRequest;
use App\Http\Requests\Admin\Order\CancelOrderRequest;
use App\Http\Resources\Admin\Order\CanceledOrderResource;
use App\Http\Resources\Admin\Order\OrderListResource;
use App\Http\Resources\Admin\Order\OrderMinimalResource;
use App\Http\Resources\Admin\Order\OrderResource;
use App\Models\Order;
use App\Services\Order\AdminOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends BaseApiController
{
    public function __construct(private readonly AdminOrderService $adminOrderService) {}

    public function index(): AnonymousResourceCollection
    {
        $orders = Order::select('id', 'store_id', 'store_branch_id', 'order_number', 'order_status', 'payment_status', 'total', 'created_at')
            ->with(['store:id,name', 'storeBranch:id,name'])
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return OrderListResource::collection($orders);
    }

    public function show(Order $order): JsonResponse
    {
        $order->load([
            'store:id,name',
            'storeBranch:id,name',
            'items:id,order_id,product_id,product_name,quantity,unit_price,subtotal'
        ]);

        $order->setRelation('delivery', $order);
        return $this->apiResponseShow(new OrderResource($order));
    }

    /**
     * Manually assign a rider to an order.
     */
    public function assignRider(AssignRiderRequest $request, string $orderId)
    {
        $data = $request->validated();
        $order = $this->adminOrderService->assignRider($orderId, $data['rider_id']);
        return $this->apiResponseUpdated(new OrderMinimalResource($order));
    }

    /**
     * Cancel an order with an optional note.
     */
    public function cancel(CancelOrderRequest $request, string $orderId)
    {
        $data = $request->validated();
        $order = $this->adminOrderService->cancelOrder($orderId, $data['note']);
        return $this->apiResponse(new CanceledOrderResource($order));
    }

    /**
     * Extend the automatic rider search for another 5 minutes.
     */
    public function extendSearch(string $orderId)
    {
        $order = $this->adminOrderService->extendSearch($orderId);
        return $this->apiResponse(new OrderMinimalResource($order));
    }
}