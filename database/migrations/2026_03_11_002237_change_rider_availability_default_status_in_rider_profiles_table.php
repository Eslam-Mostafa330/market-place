<?php

use App\Enums\RiderAvailability;
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
        Schema::table('rider_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('rider_availability')->default(RiderAvailability::default())->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rider_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('rider_availability')->change();
        });
    }
};
