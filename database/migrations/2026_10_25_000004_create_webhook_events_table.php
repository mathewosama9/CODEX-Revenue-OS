<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every inbound webhook lands here first (ADR-010). The unique key makes Salla's
 * 3 retries and Meta's up-to-7-day duplicates harmless. The raw payload holds
 * PII, so it is encrypted and kept only briefly (retention job, M10).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->string('external_event_id', 128);
            $table->string('event_type', 100);
            $table->foreignId('tenant_id')->nullable()->constrained('teams')->nullOnDelete();
            $table->text('payload');
            $table->string('status', 20)->default('received');
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestampTz('received_at')->useCurrent();
            $table->timestampTz('processed_at')->nullable();

            $table->unique(['provider', 'external_event_id']);
            $table->index(['status', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
