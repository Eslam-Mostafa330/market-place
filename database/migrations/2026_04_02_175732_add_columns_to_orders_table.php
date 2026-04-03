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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('cancelled_by')->nullable()->after('delivered_at');
            $table->unsignedTinyInteger('cancellation_reason')->nullable()->after('cancelled_by');
            $table->string('cancellation_note')->nullable()->after('cancellation_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cancelled_by', 'cancellation_reason', 'cancellation_note']);
        });
    }
};
