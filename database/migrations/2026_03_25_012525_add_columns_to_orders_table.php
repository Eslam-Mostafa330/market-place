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
        /**
         * Adds a snapshots that agreed at the moment of purchase, and Rider assignment tracking to know how many times we tried to find a rider
         * - Financial snapshot
         * - Delivery address snapshot
         */
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->after('total');
            $table->decimal('commission_amount', 10, 2)->after('commission_rate');
            $table->decimal('vendor_earnings', 10, 2)->after('commission_amount');
            $table->decimal('rider_earnings', 10, 2)->after('vendor_earnings');
            $table->string('delivery_address_line')->after('rider_earnings');
            $table->string('delivery_city')->after('delivery_address_line');
            $table->string('delivery_state')->after('delivery_city');
            $table->string('delivery_country')->after('delivery_state');
            $table->string('delivery_postal_code')->nullable()->after('delivery_country');
            $table->text('delivery_notes')->nullable()->after('delivery_postal_code');
            $table->string('delivery_phone')->nullable()->after('delivery_notes');
            $table->decimal('delivery_latitude', 10, 8)->nullable()->after('delivery_phone');
            $table->decimal('delivery_longitude', 11, 8)->nullable()->after('delivery_latitude');
            $table->unsignedTinyInteger('rider_assignment_attempts')->default(0)->after('delivery_longitude');
            $table->timestamp('rider_search_started_at')->nullable()->after('rider_assignment_attempts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'commission_amount', 'vendor_earnings', 'delivery_address_line', 'delivery_city', 'delivery_state', 'delivery_country', 'delivery_postal_code', 'delivery_notes', 'delivery_phone', 'delivery_latitude', 'delivery_longitude', 'rider_assignment_attempts', 'rider_search_started_at']);
        });
    }
};
