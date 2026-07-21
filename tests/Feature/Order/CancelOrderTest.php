<?php

use App\Enums\CancellationReason;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Events\Order\OrderCancelled;
use App\Models\Coupon;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreBranch;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Order\CancelOrderService;
use App\Services\Order\PlaceOrderService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

beforeEach(function () {
    Notification::fake();
    Queue::fake();

    $this->store    = Store::factory()->commission(10)->create();
    $this->branch   = StoreBranch::factory()->for($this->store)->active(deliveryFee: 15)->create();
    $this->customer = User::factory()->customer()->create();

    $this->actingAs($this->customer);

    $this->address = UserAddress::factory()->create();
});

function cancellableProduct(int $quantity, float $price): Product
{
    return Product::factory()->for(test()->store)->stocked($quantity, $price)->create();
}

function placeCancellableOrder(array $items, array $overrides = []): Order
{
    ['order' => $order] = app(PlaceOrderService::class)->handle([
        'store_branch_id' => test()->branch->id,
        'address_id'      => test()->address->id,
        'items'           => $items,
        'payment_method'  => PaymentMethod::CASH->value,
        ...$overrides,
    ]);

    return $order;
}

function cancelOrder(Order $order, ?string $customerId = null): Order
{
    return app(CancelOrderService::class)->cancel(
        orderId: $order->id,
        reason: CancellationReason::CHANGED_MIND,
        note: 'Test cancellation',
        cancelledBy: 'customer',
        customerId: $customerId,
    );
}

it('marks the order cancelled', function () {
    $product = cancellableProduct(quantity: 10, price: 50);
    $order   = placeCancellableOrder([['product_id' => $product->id, 'quantity' => 1]]);

    cancelOrder($order);

    expect($order->fresh()->order_status)->toBe(OrderStatus::CANCELLED)
        ->and($order->fresh()->cancelled_by)->toBe('customer');
});

/**
 * The core invariant: cancelling returns the catalog to its pre-order state.
 */
it('restores stock for every item', function () {
    $first  = cancellableProduct(quantity: 10, price: 50);
    $second = cancellableProduct(quantity: 8, price: 20);

    $order = placeCancellableOrder([
        ['product_id' => $first->id, 'quantity' => 3],
        ['product_id' => $second->id, 'quantity' => 2],
    ]);

    expect($first->fresh()->quantity)->toBe(7)
        ->and($second->fresh()->quantity)->toBe(6);

    cancelOrder($order);

    expect($first->fresh()->quantity)->toBe(10)
        ->and($second->fresh()->quantity)->toBe(8);
});

it('restores stock for merged duplicate lines', function () {
    $product = cancellableProduct(quantity: 10, price: 25);

    $order = placeCancellableOrder([
        ['product_id' => $product->id, 'quantity' => 3],
        ['product_id' => $product->id, 'quantity' => 2],
    ]);

    expect($product->fresh()->quantity)->toBe(5);

    cancelOrder($order);

    expect($product->fresh()->quantity)->toBe(10);
});

it('refunds wallet balance', function () {
    $product = cancellableProduct(quantity: 10, price: 100);

    CustomerProfile::factory()->for($this->customer)->withWallet(500)->create();

    $order = placeCancellableOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['use_wallet' => true],
    );

    expect($order->wallet_discount)->to_be_money(50)
        ->and($this->customer->customerProfile->fresh()->wallet_balance)->to_be_money(450);

    cancelOrder($order);

    expect($this->customer->customerProfile->fresh()->wallet_balance)->to_be_money(500);
});

it('releases the coupon usage count', function () {
    $product = cancellableProduct(quantity: 10, price: 100);
    $coupon  = Coupon::factory()->fixed(10)->create();

    $order = placeCancellableOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['coupon_code' => $coupon->code],
    );

    expect((int) $coupon->fresh()->used_count)->toBe(1);

    cancelOrder($order);

    expect((int) $coupon->fresh()->used_count)->toBe(0);
});

/**
 * A cancelled order must not keep consuming the customer's per-user allowance,
 * otherwise cancelling burns the coupon permanently.
 */
it('frees the per-user coupon allowance when cancelled', function () {
    $product = cancellableProduct(quantity: 10, price: 100);
    $coupon  = Coupon::factory()->fixed(10)->create(['usage_limit_per_user' => 1]);

    $first = placeCancellableOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['coupon_code' => $coupon->code],
    );

    cancelOrder($first);

    $second = placeCancellableOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['coupon_code' => $coupon->code],
    );

    expect($second->discount)->to_be_money(10);
});

it('rejects a per-user coupon already used on a live order', function () {
    $product = cancellableProduct(quantity: 10, price: 100);
    $coupon  = Coupon::factory()->fixed(10)->create(['usage_limit_per_user' => 1]);

    placeCancellableOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['coupon_code' => $coupon->code],
    );

    placeCancellableOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['coupon_code' => $coupon->code],
    );
})->throws(UnprocessableEntityHttpException::class);

/**
 * Compensation must be applied exactly once. Before the row lock and status
 * guard, two cancellations both passed the check and restored stock twice.
 */
it('cannot be cancelled twice', function () {
    $product = cancellableProduct(quantity: 10, price: 50);
    $order   = placeCancellableOrder([['product_id' => $product->id, 'quantity' => 4]]);

    cancelOrder($order);
    expect($product->fresh()->quantity)->toBe(10);

    expect(fn () => cancelOrder($order))->toThrow(UnprocessableEntityHttpException::class);

    expect($product->fresh()->quantity)->toBe(10);
});

it('cannot cancel a delivered order', function () {
    $product = cancellableProduct(quantity: 10, price: 50);
    $order   = placeCancellableOrder([['product_id' => $product->id, 'quantity' => 1]]);

    $order->update(['order_status' => OrderStatus::DELIVERED]);

    cancelOrder($order);
})->throws(UnprocessableEntityHttpException::class);

it('does not restore stock for a delivered order', function () {
    $product = cancellableProduct(quantity: 10, price: 50);
    $order   = placeCancellableOrder([['product_id' => $product->id, 'quantity' => 4]]);

    $order->update(['order_status' => OrderStatus::DELIVERED]);

    try {
        cancelOrder($order);
    } catch (UnprocessableEntityHttpException) {
    }

    expect($product->fresh()->quantity)->toBe(6);
});

/**
 * Scoping by customer id is what stops one customer cancelling another's order.
 */
it('does not let a customer cancel another customers order', function () {
    $product  = cancellableProduct(quantity: 10, price: 50);
    $order    = placeCancellableOrder([['product_id' => $product->id, 'quantity' => 1]]);
    $intruder = User::factory()->customer()->create();

    cancelOrder($order, customerId: $intruder->id);
})->throws(ModelNotFoundException::class);

/**
 * The service's own contract is that it announces the cancellation; who reacts to
 * that is the listeners' concern and is covered by their own tests.
 */
it('announces the cancellation', function () {
    Event::fake([OrderCancelled::class]);

    $product = cancellableProduct(quantity: 10, price: 50);
    $order   = placeCancellableOrder([['product_id' => $product->id, 'quantity' => 1]]);

    cancelOrder($order);

    Event::assertDispatched(OrderCancelled::class, fn ($event) => $event->orderId === $order->id);
});

it('does not announce a rejected cancellation', function () {
    Event::fake([OrderCancelled::class]);

    $product = cancellableProduct(quantity: 10, price: 50);
    $order   = placeCancellableOrder([['product_id' => $product->id, 'quantity' => 1]]);

    $order->update(['order_status' => OrderStatus::DELIVERED]);

    try {
        cancelOrder($order);
    } catch (UnprocessableEntityHttpException) {
    }

    Event::assertNotDispatched(OrderCancelled::class);
});
