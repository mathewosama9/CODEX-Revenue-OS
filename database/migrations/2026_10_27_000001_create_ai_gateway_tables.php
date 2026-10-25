<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M07 AiGateway (E10, ADR-007/008). `ai_calls` keeps metadata and cost only,
 * never the prompt or the reply (09 §9.5). `eval_runs` is CODEX-level: the
 * golden set is written by us, not taken from a tenant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('purpose', 30);
            $table->string('conversation_ref', 64)->nullable();
            $table->string('provider', 30);
            $table->string('model', 60);
            $table->string('status', 20);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_read_tokens')->default(0);
            $table->unsignedInteger('cache_write_tokens')->default(0);
            $table->unsignedBigInteger('cost_usd_micros')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->unsignedSmallInteger('tool_calls')->default(0);
            $table->unsignedSmallInteger('pseudonymized')->default(0);
            $table->string('error', 120)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'conversation_ref']);
        });

        Schema::create('eval_runs', function (Blueprint $table) {
            $table->id();
            $table->string('suite', 40);
            $table->string('provider', 30);
            $table->string('model', 60);
            $table->string('prompt_version', 20);
            $table->unsignedInteger('cases');
            $table->unsignedInteger('passed');
            $table->unsignedInteger('price_cases');
            $table->unsignedInteger('price_passed');
            $table->boolean('blocking_failure');
            $table->unsignedBigInteger('cost_usd_micros')->default(0);
            $table->jsonb('failures');
            $table->timestampTz('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eval_runs');
        Schema::dropIfExists('ai_calls');
    }
};
