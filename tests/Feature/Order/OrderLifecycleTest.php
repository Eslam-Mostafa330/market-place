<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\SettingKey;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\Product;
use App\Models\RiderPayout;
use App\Models\Setting;
use App\Models\Store;
use App\Models\StoreBranch;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\VendorPayout;
use App\Models\VendorProfile;
use App\Services\Order\RiderOrderService;
use App\Services\Order\VendorOrderService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Notification::fake();
    Queue::fake();

    $this->vendorUser = User::factory()->vendor()->create();
    $profile          = VendorProfile::factory()->create(['user_id' => $this->vendorUser->id]);

    $this->store    = Store::factory()->commission(10)->create(['vendor_profile_id' => $profile->id]);
    $this->branch   = StoreBranch::factory()->for($this->store)->active(deliveryFee: 20)->create();
    $this->rider    = User::factory()->rider()->create();
    $this->customer = User::factory()->customer()->create();

    CustomerProfile::factory()->for($this->customer)->create();

    $this->actingAs($this->customer);
    $this->address = UserAddress::factory()->create();

    $this->product = Product::factory()->for($this->store)->stocked(quantity: 20, price: 100)->create();
});

/**
 * Place an order through the real HTTP endpoint.
 */
function placeOrderViaApi(array $overrides = [])
{
    return test()->postJson('/api/v1/customer/orders', [
        'store_branch_id' => test()->branch->id,
        'address_id'      => test()->address->id,
        'items'           => [['product_id' => test()->product->id, 'quantity' => 2]],
        'payment_method'  => PaymentMethod::CASH->value,
        ...$overrides,
    ]);
}

it('places an order through the api', function () {
    $response = placeOrderViaApi()->assertCreated();

    $order = Order::first();

    expect($order)->not->toBeNull()
        ->and($order->customer_id)->toBe($this->customer->id)
        ->and($order->order_status)->toBe(OrderStatus::PENDING)
        ->and($order->subtotal)->to_be_money(200)
        ->and($order->total)->to_be_money(220)
        ->and($this->product->fresh()->quantity)->toBe(18);
});

it('rejects an unauthenticated order', function () {
    $this->app['auth']->forgetGuards();

    $this->postJson('/api/v1/customer/orders', [])->assertUnauthorized();
});

it('validates the order payload', function () {
    placeOrderViaApi(['items' => []])->assertApiValidationErrors('items');
});

it('lists only the authenticated customer’s orders', function () {
    placeOrderViaApi()->assertCreated();

    $other = User::factory()->customer()->create();
    Order::factory()->create([
        'customer_id'     => $other->id,
        'store_id'        => $this->store->id,
        'store_branch_id' => $this->branch->id,
    ]);

    $response = $this->getJson('/api/v1/customer/orders')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

it('cancels an order through the api and restores stock', function () {
    placeOrderViaApi()->assertCreated();

    $order = Order::first();
    expect($this->product->fresh()->quantity)->toBe(18);

    $this->postJson("/api/v1/customer/orders/{$order->id}/cancel", [
        'reason' => \App\Enums\CancellationReason::CHANGED_MIND->value,
        'note'   => 'Changed my mind',
    ])->assertOk();

    expect($order->fresh()->order_status)->toBe(OrderStatus::CANCELLED)
        ->and($this->product->fresh()->quantity)->toBe(20);
});

/**
 * Walks an order the whole way from placement to delivery, checking that each
 * actor can only make its own transition and that delivery settles the money.
 */
it('runs the full order lifecycle to delivery', function () {
    Setting::query()->updateOrCreate(
        ['key' => SettingKey::LOYALTY_POINTS->value],
        ['value' => 1],
    );

    cache()->forget('loyalty_points');

    placeOrderViaApi()->assertCreated();
    $order = Order::first();

    $vendorService = app(VendorOrderService::class);
    $riderService  = app(RiderOrderService::class);

    $this->actingAs($this->vendorUser);

    $vendorService->acceptOrder($order->id);
    expect($order->fresh()->order_status)->toBe(OrderStatus::ACCEPTED);

    $vendorService->prepareOrder($order->id);
    expect($order->fresh()->order_status)->toBe(OrderStatus::PREPARING);

    $vendorService->markReady($order->id);
    expect($order->fresh()->order_status)->toBe(OrderStatus::WAITING_RIDER);

    $order->update(['rider_id' => $this->rider->id, 'order_status' => OrderStatus::RIDER_ASSIGNED]);

    $this->actingAs($this->rider);

    $riderService->pickupOrder($order->id, $this->rider->id);
    expect($order->fresh()->order_status)->toBe(OrderStatus::PICKED_UP);

    $riderService->deliverOrder($order->id, $this->rider->id);

    $delivered = $order->fresh();

    expect($delivered->order_status)->toBe(OrderStatus::DELIVERED)
        ->and($delivered->delivered_at)->not->toBeNull()
        ->and($delivered->payment_status)->toBe(PaymentStatus::PAID);

    expect($delivered->commission_amount)->to_be_money(20)
        ->and($delivered->vendor_earnings)->to_be_money(180)
        ->and($delivered->rider_earnings)->to_be_money(20);

    expect((int) $this->customer->customerProfile->fresh()->loyalty_points)->toBe(200);
});

/**
 * Payout records exist only for card orders; cash is settled hand to hand.
 */
it('does not create payout records for a cash order', function () {
    placeOrderViaApi()->assertCreated();
    $order = Order::first();

    $this->actingAs($this->vendorUser);
    app(VendorOrderService::class)->acceptOrder($order->id);
    app(VendorOrderService::class)->prepareOrder($order->id);
    app(VendorOrderService::class)->markReady($order->id);

    $order->update(['rider_id' => $this->rider->id, 'order_status' => OrderStatus::RIDER_ASSIGNED]);

    $this->actingAs($this->rider);
    app(RiderOrderService::class)->pickupOrder($order->id, $this->rider->id);
    app(RiderOrderService::class)->deliverOrder($order->id, $this->rider->id);

    expect(VendorPayout::count())->toBe(0)
        ->and(RiderPayout::count())->toBe(0);
});

it('creates pending payouts for a delivered card order', function () {
    $order = Order::factory()->paidByCard()->create([
        'customer_id'     => $this->customer->id,
        'store_id'        => $this->store->id,
        'store_branch_id' => $this->branch->id,
        'rider_id'        => $this->rider->id,
        'order_status'    => OrderStatus::PICKED_UP,
        'vendor_earnings' => 180,
        'rider_earnings'  => 20,
    ]);

    $this->actingAs($this->rider);
    app(RiderOrderService::class)->deliverOrder($order->id, $this->rider->id);

    expect(VendorPayout::where('order_id', $order->id)->first())
        ->not->toBeNull()
        ->status->toBe(PayoutStatus::PENDING)
        ->and(RiderPayout::where('order_id', $order->id)->first())
        ->not->toBeNull()
        ->status->toBe(PayoutStatus::PENDING);
});

/**
 * The transitions are ordered; a vendor cannot mark an order ready before it has
 * been accepted and prepared.
 */
it('refuses an out-of-order transition', function () {
    placeOrderViaApi()->assertCreated();
    $order = Order::first();

    $this->actingAs($this->vendorUser);

    app(VendorOrderService::class)->markReady($order->id);
})->throws(Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException::class);

/**
 * Delivery is terminal, so a repeated call must not award loyalty points twice.
 */
it('cannot deliver the same order twice', function () {
    $order = Order::factory()->create([
        'customer_id'     => $this->customer->id,
        'store_id'        => $this->store->id,
        'store_branch_id' => $this->branch->id,
        'rider_id'        => $this->rider->id,
        'order_status'    => OrderStatus::PICKED_UP,
    ]);

    $this->actingAs($this->rider);
    app(RiderOrderService::class)->deliverOrder($order->id, $this->rider->id);

    $pointsAfterFirst = (int) $this->customer->customerProfile->fresh()->loyalty_points;

    expect(fn () => app(RiderOrderService::class)->deliverOrder($order->id, $this->rider->id))
        ->toThrow(Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException::class);

    expect((int) $this->customer->customerProfile->fresh()->loyalty_points)->toBe($pointsAfterFirst);
});
