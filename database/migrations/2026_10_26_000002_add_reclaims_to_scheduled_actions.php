<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How many times a claimed action was picked up again after its worker went
 * quiet (review H1). Separate from `attempts`, which counts real send retries
 * and is part of the send idempotency key.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_actions', function (Blueprint $table) {
            $table->unsignedSmallInteger('reclaims')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_actions', function (Blueprint $table) {
            $table->dropColumn('reclaims');
        });
    }
};
