<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M08 recovery (E05, ADR-010/014). `scheduled_actions` is the single source of
 * truth for every delay: business state lives in the database, not the queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recovery_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('teams')->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->unsignedInteger('first_delay_minutes')->default(60);
            $table->unsignedInteger('second_delay_minutes')->default(1440);
            $table->unsignedSmallInteger('max_messages')->default(2);
            $table->bigInteger('min_cart_halalas')->default(0);
            $table->unsignedSmallInteger('frequency_days')->default(7);
            $table->unsignedSmallInteger('max_cart_age_hours')->default(48);
            $table->boolean('discount_enabled')->default(false);
            $table->unsignedSmallInteger('discount_percent')->default(0);
            $table->string('discount_code', 64)->nullable();
            $table->unsignedSmallInteger('discount_cap_percent')->default(10);
            $table->unsignedSmallInteger('margin_floor_bps')->default(1000);
            $table->bigInteger('monthly_discount_budget_halalas')->default(0);
            $table->unsignedInteger('approval_window_minutes')->default(240);
            $table->timestampsTz();
        });

        Schema::create('recovery_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedBigInteger('cart_id');
            $table->char('subject_hash', 64)->nullable();
            $table->string('arm', 10)->nullable();
            $table->string('status', 20);
            $table->string('reason', 40)->nullable();
            $table->timestampTz('started_at');
            $table->timestampTz('ended_at')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'cart_id']);
            $table->index(['tenant_id', 'subject_hash', 'started_at']);
            $table->index(['tenant_id', 'status']);
        });

        Schema::create('scheduled_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('recovery_sequence_id')->constrained('recovery_sequences')->cascadeOnDelete();
            $table->unsignedSmallInteger('step');
            $table->string('status', 20);
            $table->timestampTz('run_at');
            $table->string('idempotency_key', 128);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('last_error')->nullable();
            $table->timestampTz('claimed_at')->nullable();
            $table->jsonb('result')->default('{}');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'idempotency_key']);
            $table->index(['status', 'run_at']);
            $table->index(['tenant_id', 'recovery_sequence_id']);
        });

        Schema::create('discount_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('scheduled_action_id')->constrained('scheduled_actions')->cascadeOnDelete();
            $table->unsignedSmallInteger('percent');
            $table->string('reason', 40);
            $table->string('status', 20);
            $table->timestampTz('expires_at');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_approvals');
        Schema::dropIfExists('scheduled_actions');
        Schema::dropIfExists('recovery_sequences');
        Schema::dropIfExists('recovery_settings');
    }
};
