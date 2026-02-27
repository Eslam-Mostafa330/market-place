<?php

use App\Enums\DefineStatus;
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
        Schema::create('store_branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->text('address');
            $table->string('city');
            $table->string('area')->nullable();
            $table->string('phone')->nullable();
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->unsignedSmallInteger('delivery_time_max')->default(60); // Minutes
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            $table->unsignedTinyInteger('status')->default(DefineStatus::default());
            $table->unique(['store_id', 'name']);
            $table->unique(['store_id', 'slug']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_branches');
    }
};
