<?php

use App\Enums\DefineStatus;
use App\Enums\PaymentMethod;
use App\Models\Coupon;
use App\Models\CustomerProfile;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreBranch;
use App\Models\User;
use App\Models\UserAddress;
use App\Services\Order\PlaceOrderService;
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

/**
 * Build an active product belonging to the branch's store.
 */
function orderableProduct(int $quantity, float $price): Product
{
    return Product::factory()->for(test()->store)->stocked($quantity, $price)->create();
}

/**
 * Place an order, filling in the parts a test does not care about.
 */
function placeOrder(array $items, array $overrides = []): array
{
    return app(PlaceOrderService::class)->handle([
        'store_branch_id' => test()->branch->id,
        'address_id'      => test()->address->id,
        'items'           => $items,
        'payment_method'  => PaymentMethod::CASH->value,
        ...$overrides,
    ]);
}

it('places an order and decrements stock', function () {
    $product = orderableProduct(quantity: 10, price: 50);

    ['order' => $order] = placeOrder([
        ['product_id' => $product->id, 'quantity' => 2],
    ]);

    expect($order->subtotal)->to_be_money(100)
        ->and($order->delivery_fee)->to_be_money(15)
        ->and($order->total)->to_be_money(115)
        ->and($product->fresh()->quantity)->toBe(8);
});

/**
 * The same product across several lines must be treated as one combined quantity.
 * Validating each line separately let a cart of 3 x 4 units pass against a stock
 * of 5, and the bulk CASE decrement only applied the first matching branch, so
 * stock fell by 4 instead of 12.
 */
it('merges duplicate product lines into one quantity', function () {
    $product = orderableProduct(quantity: 10, price: 25);

    ['order' => $order] = placeOrder([
        ['product_id' => $product->id, 'quantity' => 3],
        ['product_id' => $product->id, 'quantity' => 2],
    ]);

    expect($order->items)->toHaveCount(1)
        ->and((int) $order->items->first()->quantity)->toBe(5)
        ->and($order->subtotal)->to_be_money(125)
        ->and($product->fresh()->quantity)->toBe(5);
});

it('rejects duplicate lines whose combined quantity exceeds stock', function () {
    $product = orderableProduct(quantity: 5, price: 10);

    placeOrder([
        ['product_id' => $product->id, 'quantity' => 4],
        ['product_id' => $product->id, 'quantity' => 4],
    ]);
})->throws(UnprocessableEntityHttpException::class);

it('does not decrement stock when placement fails', function () {
    $product = orderableProduct(quantity: 5, price: 10);

    try {
        placeOrder([
            ['product_id' => $product->id, 'quantity' => 4],
            ['product_id' => $product->id, 'quantity' => 4],
        ]);
    } catch (UnprocessableEntityHttpException) {
    }

    expect($product->fresh()->quantity)->toBe(5);
});

/**
 * maximum_discount was absent from the column list validateCoupon selected, so the
 * cap read as null on the partially loaded model and was silently skipped — a
 * "20% off, max 10" coupon discounted without limit.
 */
it('caps a percentage coupon at its maximum discount', function () {
    $product = orderableProduct(quantity: 10, price: 500);
    $coupon  = Coupon::factory()->percentage(value: 20, maximumDiscount: 10)->create();

    ['order' => $order] = placeOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['coupon_code' => $coupon->code],
    );

    expect($order->discount)->to_be_money(10)
        ->and($order->total)->to_be_money(505);
});

it('applies an uncapped percentage coupon in full', function () {
    $product = orderableProduct(quantity: 10, price: 200);
    $coupon  = Coupon::factory()->percentage(value: 25)->create();

    ['order' => $order] = placeOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['coupon_code' => $coupon->code],
    );

    expect($order->discount)->to_be_money(50)
        ->and($order->total)->to_be_money(165);
});

/**
 * minimum_order exists as a column but was never read by any code path.
 */
it('rejects a coupon below its minimum order', function () {
    $product = orderableProduct(quantity: 10, price: 20);
    $coupon  = Coupon::factory()->fixed(5)->minimumOrder(100)->create();

    placeOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['coupon_code' => $coupon->code],
    );
})->throws(UnprocessableEntityHttpException::class);

it('accepts a coupon that meets its minimum order', function () {
    $product = orderableProduct(quantity: 10, price: 100);
    $coupon  = Coupon::factory()->fixed(5)->minimumOrder(100)->create();

    ['order' => $order] = placeOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['coupon_code' => $coupon->code],
    );

    expect($order->discount)->to_be_money(5);
});

it('increments coupon usage on placement', function () {
    $product = orderableProduct(quantity: 10, price: 100);
    $coupon  = Coupon::factory()->fixed(5)->create();

    placeOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['coupon_code' => $coupon->code],
    );

    expect((int) $coupon->fresh()->used_count)->toBe(1);
});

/**
 * Wallet may cover at most half of the discounted subtotal; the delivery fee
 * always stays payable.
 */
it('caps wallet discount at half the subtotal', function () {
    $product = orderableProduct(quantity: 10, price: 100);

    CustomerProfile::factory()->for($this->customer)->withWallet(500)->create();

    ['order' => $order] = placeOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['use_wallet' => true],
    );

    expect($order->wallet_discount)->to_be_money(50)
        ->and($order->total)->to_be_money(65)
        ->and($this->customer->customerProfile->fresh()->wallet_balance)->to_be_money(450);
});

/**
 * A customer with no profile row used to fatal on ->wallet_balance.
 */
it('places an order when wallet requested but no profile exists', function () {
    $product = orderableProduct(quantity: 10, price: 100);

    ['order' => $order] = placeOrder(
        [['product_id' => $product->id, 'quantity' => 1]],
        ['use_wallet' => true],
    );

    expect($order->wallet_discount)->to_be_money(0)
        ->and($order->total)->to_be_money(115);
});

it('rejects an inactive branch', function () {
    $product = orderableProduct(quantity: 10, price: 100);

    $this->branch->update(['status' => DefineStatus::INACTIVE]);

    placeOrder([['product_id' => $product->id, 'quantity' => 1]]);
})->throws(UnprocessableEntityHttpException::class);

it('rejects an address belonging to another customer', function () {
    $product = orderableProduct(quantity: 10, price: 100);

    $this->actingAs(User::factory()->customer()->create());
    $foreignAddress = UserAddress::factory()->create();

    $this->actingAs($this->customer);
    $this->address = $foreignAddress;

    placeOrder([['product_id' => $product->id, 'quantity' => 1]]);
})->throws(UnprocessableEntityHttpException::class);

it('rejects a product from another store', function () {
    $foreign = Product::factory()->stocked(10, 50)->create();

    placeOrder([['product_id' => $foreign->id, 'quantity' => 1]]);
})->throws(UnprocessableEntityHttpException::class);

it('generates a unique order number for every order', function () {
    $product = orderableProduct(quantity: 100, price: 10);

    $numbers = collect(range(1, 5))->map(function () use ($product) {
        ['order' => $order] = placeOrder([['product_id' => $product->id, 'quantity' => 1]]);

        return $order->order_number;
    });

    expect($numbers->unique())->toHaveCount(5);
});
