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
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vendor_id')->constrained('vendor_profiles')->onDelete('cascade'); // The default foreign key name has already been overwritten using a new migration file to be "vendor_profile_id"
            $table->foreignUuid('business_category_id')->constrained('business_categories')->onDelete('cascade');
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable(); // This attribute has already been overwritten using a new migration file to "image"
            $table->unsignedTinyInteger('status')->default(DefineStatus::default()); // This attribute has already been removed using a new migration file
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
