<?php

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
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('users')->onDelete('restrict');
            $table->foreignUuid('store_id')->constrained('stores')->onDelete('restrict');
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('restrict');
            $table->unsignedTinyInteger('rate')->default(0);
            $table->text('full_review')->nullable();
            $table->unique(['customer_id', 'order_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
