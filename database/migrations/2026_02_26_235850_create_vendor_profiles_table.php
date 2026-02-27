<?php

use App\Enums\VendorVerificationStatus;
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
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('business_name');
            $table->string('business_license')->nullable();
            $table->text('business_description')->nullable();
            $table->string('business_phone', 25)->nullable();
            $table->string('business_email')->nullable();
            $table->decimal('rating', 3, 2)->default(0)->nullable();
            $table->unsignedMediumInteger('total_orders')->default(0);
            $table->unsignedTinyInteger('verification_status')->default(VendorVerificationStatus::default());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
