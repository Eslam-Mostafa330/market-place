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
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['vendor_id']);
            $table->renameColumn('vendor_id', 'vendor_profile_id');
            $table->renameColumn('cover_image', 'image');
            $table->dropColumn('status');
        });

        // Recreate the new foreign key
        Schema::table('stores', function (Blueprint $table) {
            $table->foreign('vendor_profile_id')->references('id')->on('vendor_profiles')->onDelete('cascade');
        });

        Schema::table('business_categories', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_categories', function (Blueprint $table) {
            $table->unsignedTinyInteger('status')->default(DefineStatus::default());
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeign(['vendor_profile_id']);
            $table->renameColumn('vendor_profile_id', 'vendor_id');
            $table->renameColumn('image', 'cover_image');
            $table->unsignedTinyInteger('status')->default(DefineStatus::default());
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->foreign('vendor_id')->references('id')->on('vendor_profiles')->onDelete('cascade');
        });
    }
};
