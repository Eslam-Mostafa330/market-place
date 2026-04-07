<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Api\V1\Customer\Concerns\CustomerOrderAuthorization;
use App\Http\Requests\Customer\Order\CancelOrderRequest;
use App\Http\Requests\Customer\Order\PlaceOrderRequest;
use App\Http\Resources\Customer\Order\OrderCancellationResource;
use App\Http\Resources\Customer\Order\OrderListResource;
use App\Http\Resources\Customer\Order\OrderResource;
use App\Models\Order;
use App\Services\Order\CustomerOrderService;
use App\Services\Order\PlaceOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrderController extends BaseApiController
{
    use CustomerOrderAuthorization;
    
    public function __construct(
        private readonly PlaceOrderService    $placeOrderService,
        private readonly CustomerOrderService $customerOrderService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $orders = Order::select('id', 'store_id', 'store_branch_id', 'order_number', 'order_status', 'payment_status', 'total', 'created_at')
            ->where('customer_id', auth()->user()->id)
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
     * Customer places a new order.
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        $orderData = $request->validated();
        $order = $this->placeOrderService->handle($orderData);
        $order->setRelation('delivery', $order);
        return $this->apiResponseStored(new OrderResource($order->load('items')));
    }

    /**
     * Customer cancels their order.
     */
    public function cancel(CancelOrderRequest $request, Order $order)
    {
        $this->authorizeOrder($order);
        $data = $request->validated();
        $order = $this->customerOrderService->cancelOrder($order, $data['reason'], $data['note']);
        return $this->apiResponse(new OrderCancellationResource($order));
    }
}