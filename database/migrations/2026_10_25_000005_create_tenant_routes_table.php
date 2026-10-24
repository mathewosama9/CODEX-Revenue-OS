<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maps an external account (Salla merchant id, Meta phone_number_id) to its
 * tenant. Webhooks resolve their tenant here — never from the payload (09 §9.14.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_routes', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->string('external_id', 64);
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->timestampsTz();

            $table->unique(['provider', 'external_id']);
            $table->index(['tenant_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_routes');
    }
};
