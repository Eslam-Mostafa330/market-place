<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Vendor\Concerns\VendorOrderStoreAuthorization;
use App\Http\Resources\Vendor\Order\OrderListResource;
use App\Http\Resources\Vendor\Order\OrderResource;
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
     * Accepts the pending order.
     */
    public function accept(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);
        $acceptedOrder = $this->vendorOrderService->acceptOrder($order);
        return $this->apiResponseUpdated(new OrderListResource($acceptedOrder));
    }
}