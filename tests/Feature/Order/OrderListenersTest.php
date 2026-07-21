<?php

use App\Events\Order\OrderCancelled;
use App\Events\Order\OrderPlaced;
use App\Events\Order\OrderStatusChanged;
use App\Enums\OrderStatus;
use App\Jobs\Order\ReverseOrderPaymentJob;
use App\Listeners\Order\NotifyCustomerOfCancelledOrder;
use App\Listeners\Order\NotifyCustomerOfStatusChange;
use App\Listeners\Order\NotifyVendorOfPlacedOrder;
use App\Listeners\Order\ReverseGatewayPaymentForCancelledOrder;
use App\Models\Order;
use App\Models\Store;
use App\Models\StoreBranch;
use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\Order\NewOrderNotification;
use App\Notifications\Order\OrderCancelledNotification;
use App\Notifications\Order\OrderStatusUpdatedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Notification::fake();
    Queue::fake();
});

function listenerOrder(array $overrides = []): Order
{
    $vendorUser = User::factory()->vendor()->create();
    $profile    = VendorProfile::factory()->create(['user_id' => $vendorUser->id]);
    $store      = Store::factory()->create(['vendor_profile_id' => $profile->id]);
    $branch     = StoreBranch::factory()->for($store)->active(deliveryFee: 10)->create();

    return Order::factory()->create([
        'customer_id'     => User::factory()->customer()->create()->id,
        'store_id'        => $store->id,
        'store_branch_id' => $branch->id,
        ...$overrides,
    ]);
}

it('notifies the vendor of a placed order', function () {
    $order      = listenerOrder();
    $vendorUser = $order->store->vendorProfile->user;

    app(NotifyVendorOfPlacedOrder::class)->handle(OrderPlaced::from($order));

    Notification::assertSentTo($vendorUser, NewOrderNotification::class);
});

/**
 * Listeners run asynchronously, so the order they were told about may be gone by
 * the time they execute. That must be a no-op rather than a failed job.
 */
it('skips vendor notification when the order no longer exists', function () {
    $order = listenerOrder();
    $event = OrderPlaced::from($order);

    $order->delete();

    app(NotifyVendorOfPlacedOrder::class)->handle($event);

    Notification::assertNothingSent();
});

it('notifies the customer of a cancelled order', function () {
    $order = listenerOrder(['cancelled_by' => 'admin', 'cancellation_note' => 'Out of stock']);

    app(NotifyCustomerOfCancelledOrder::class)->handle(OrderCancelled::from($order));

    Notification::assertSentTo($order->customer, OrderCancelledNotification::class);
});

it('queues a gateway reversal for a card order', function () {
    $order = listenerOrder();
    $order->update(['payment_intent_id' => 'pi_test_123']);

    app(ReverseGatewayPaymentForCancelledOrder::class)->handle(OrderCancelled::from($order));

    Queue::assertPushed(ReverseOrderPaymentJob::class, fn ($job) => $job->orderId === $order->id);
});

it('does not queue a gateway reversal without a payment intent', function () {
    $order = listenerOrder();

    app(ReverseGatewayPaymentForCancelledOrder::class)->handle(OrderCancelled::from($order));

    Queue::assertNotPushed(ReverseOrderPaymentJob::class);
});

/**
 * The status is captured when the event is raised, not read back when the queued
 * listener runs. Otherwise an order that advanced in the meantime would be
 * announced with a status that contradicts its own message.
 */
it('reports the status captured at the moment of the change', function () {
    $order = listenerOrder(['order_status' => OrderStatus::PICKED_UP]);

    $event = OrderStatusChanged::from($order, 'Your order is on the way');

    $order->update(['order_status' => OrderStatus::DELIVERED]);

    app(NotifyCustomerOfStatusChange::class)->handle($event);

    Notification::assertSentTo(
        $order->customer,
        OrderStatusUpdatedNotification::class,
        fn ($notification) => $event->status === OrderStatus::PICKED_UP,
    );
});
