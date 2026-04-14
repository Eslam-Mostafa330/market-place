<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Vendor\Concerns\VendorOrderStoreAuthorization;
use App\Http\Resources\Vendor\Order\OrderListResource;
use App\Http\Resources\Vendor\Order\OrderResource;
use App\Http\Resources\Vendor\Order\OrderStatusResource;
use App\Models\Order;
use App\Services\Order\VendorOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends BaseApiController
{
    use VendorOrderStoreAuthorization;

    public function __construct(private readonly VendorOrderService $vendorOrderService) {}

    public function index(): AnonymousResourceCollection
    {
        $orders = Order::select('id', 'store_id', 'store_branch_id', 'order_number', 'order_status', 'payment_status', 'total', 'created_at')
            ->where('store_id', auth()->user()->store?->id)
            ->with(['store:id,name', 'storeBranch:id,name'])
            ->useFilters()
            ->latest()
            ->dynamicPaginate();

        return OrderListResource::collection($orders);
    }

    public function show(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);
        
        $order->load([
            'store:id,name',
            'storeBranch:id,name',
            'items:id,order_id,product_id,product_name,quantity,unit_price,subtotal'
        ]);

        $order->setRelation('delivery', $order);
        return $this->apiResponseShow(new OrderResource($order));
    }

    /**
     * Vendor accepts the pending order.
     */
    public function accept(string $orderId): JsonResponse
    {
        $acceptedOrder = $this->vendorOrderService->acceptOrder($orderId);
        return $this->apiResponseUpdated(new OrderStatusResource($acceptedOrder));
    }

    /**
     * Vendor prepares the order.
     */
    public function prepare(string $orderId): JsonResponse
    {
        $preparedOrder = $this->vendorOrderService->prepareOrder($orderId);
        return $this->apiResponseUpdated(new OrderStatusResource($preparedOrder));
    }

    /**
     * Vendor marks the order as ready for pickup.
     */
    public function ready(string $orderId): JsonResponse
    {
        $readyOrder = $this->vendorOrderService->markReady($orderId);
        return $this->apiResponseUpdated(new OrderStatusResource($readyOrder));
    }
}