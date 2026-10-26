<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-012: one currency and one timezone per workspace, set when its store
 * is connected. Money columns hold minor units of this currency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->string('currency', 3)->default('SAR')->after('is_personal');
            $table->string('timezone', 64)->default('Asia/Riyadh')->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['currency', 'timezone']);
        });
    }
};
