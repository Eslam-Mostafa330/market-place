<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align sessions.user_id with the application's UUID primary keys.
     *
     * The default Laravel sessions table declares user_id as a BIGINT, but
     * users use UUID identifiers. With the database session driver this
     * mismatch would prevent sessions from being stored and would make
     * per-user session revocation impossible.
     */
    public function up(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }
};
