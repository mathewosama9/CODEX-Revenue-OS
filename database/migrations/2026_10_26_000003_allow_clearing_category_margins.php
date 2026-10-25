<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A null margin is a new version that clears a category's own margin, so it
 * falls back to the store default again. History stays (review H16).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_margins', function (Blueprint $table) {
            $table->unsignedSmallInteger('margin_bps')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('category_margins', function (Blueprint $table) {
            $table->unsignedSmallInteger('margin_bps')->nullable(false)->change();
        });
    }
};
