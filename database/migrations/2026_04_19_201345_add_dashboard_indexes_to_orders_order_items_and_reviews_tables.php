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
        // Optimize order queries used in dashboard statistics and aggregations
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['store_id', 'order_status', 'created_at']);
            $table->index(['store_id', 'payment_method', 'order_status']);
        });

        // Optimize joins and aggregations for top-selling products
        Schema::table('order_items', function (Blueprint $table) {
            $table->index(['order_id', 'product_id']);
        });

        // Optimize fetching latest reviews per store
        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['store_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'order_status', 'created_at']);
            $table->dropIndex(['store_id', 'payment_method', 'order_status']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex(['order_id', 'product_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'created_at']);
        });
    }
};
