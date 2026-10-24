<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CODEX staff for the internal admin (E14): a separate table and guard from
 * merchants, with mandatory app-based 2FA.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->text('app_authentication_secret')->nullable();
            $table->text('app_authentication_recovery_codes')->nullable();
            $table->rememberToken();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
