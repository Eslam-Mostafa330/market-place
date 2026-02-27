<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('users')->onDelete('restrict');
            $table->foreignUuid('store_id')->constrained('stores')->onDelete('restrict');
            $table->foreignUuid('store_branch_id')->constrained('store_branches')->onDelete('restrict');
            $table->foreignUuid('rider_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('coupon_id')->nullable()->constrained('coupons')->onDelete('set null');
            $table->string('order_number')->unique();
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);
            $table->text('notes')->nullable();
            $table->unsignedTinyInteger('payment_method');
            $table->unsignedTinyInteger('order_status')->default(OrderStatus::default());
            $table->unsignedTinyInteger('payment_status')->default(PaymentStatus::default());
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
