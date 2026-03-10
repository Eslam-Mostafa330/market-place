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
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->default(0)->nullable(false)->change();
            $table->unique('user_id');
            $table->text('rejection_reason')->nullable()->after('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->decimal('rating', 3, 2)->default(0)->nullable()->change();
            $table->dropUnique(['user_id']);
            $table->dropColumn('rejection_reason');
        });
    }
};
