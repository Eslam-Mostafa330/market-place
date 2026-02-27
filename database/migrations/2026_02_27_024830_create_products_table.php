<?php

use App\Enums\BooleanStatus;
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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('store_id')->constrained('stores')->onDelete('cascade');
            $table->foreignUuid('product_category_id')->constrained('product_categories')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->unsignedSmallInteger('quantity');
            $table->unsignedSmallInteger('preparation_time')->default(0); // In Minutes
            $table->unsignedTinyInteger('is_featured')->default(BooleanStatus::default());
            $table->unsignedTinyInteger('status')->default(DefineStatus::INACTIVE);
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
        Schema::dropIfExists('products');
    }
};
