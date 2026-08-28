<?php

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
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
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->string('subject');
            $table->unsignedTinyInteger('category')->default(TicketCategory::default());
            $table->unsignedTinyInteger('status')->default(TicketStatus::default());
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('first_replied_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'last_message_at']);
            $table->index(['requester_id', 'status']);
            $table->index(['agent_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
