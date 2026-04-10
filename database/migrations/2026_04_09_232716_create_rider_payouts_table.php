<?php

use App\Enums\PayoutMethod;
use App\Enums\PayoutStatus;
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
        Schema::create('rider_payouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rider_id')->constrained('users')->onDelete('restrict');
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('restrict')->unique();
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('amount', 10, 2);
            $table->unsignedTinyInteger('status')->default(PayoutStatus::default())->index();
            $table->unsignedTinyInteger('payout_method')->nullable();
            $table->string('reference')->nullable();
            $table->string('payout_proof')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rider_payouts');
    }
};
