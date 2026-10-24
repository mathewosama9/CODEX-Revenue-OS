<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M09 measurement (E06/E07, ADR-013). Assignments, exposures, touches and the
 * cost ledger are append-only so every report can be re-derived and audited.
 * No message content lives here (ADR-009) — only hashes, ids and amounts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('key', 40);
            $table->unsignedSmallInteger('holdout_bps');
            $table->char('salt', 32);
            // Mandatory in Proof Pilots: the holdout cannot be switched off (US-E06-05).
            $table->boolean('holdout_locked')->default(true);
            $table->timestampTz('srm_flagged_at')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'key']);
        });

        Schema::create('experiment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('experiment_id')->constrained('experiments')->restrictOnDelete();
            $table->char('subject_hash', 64);
            $table->string('arm', 10);
            $table->timestampTz('assigned_at')->useCurrent();

            $table->unique(['tenant_id', 'experiment_id', 'subject_hash']);
        });

        // Intent-to-treat: every eligible cart is logged for BOTH arms at the same
        // decision point, so control is never filtered differently (06 S2).
        Schema::create('exposures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->foreignId('experiment_id')->constrained('experiments')->restrictOnDelete();
            $table->char('subject_hash', 64);
            $table->string('arm', 10);
            $table->unsignedBigInteger('cart_id');
            $table->bigInteger('cart_total_halalas');
            $table->timestampTz('eligible_at');

            $table->unique(['tenant_id', 'experiment_id', 'cart_id']);
            $table->index(['tenant_id', 'experiment_id', 'eligible_at']);
        });

        Schema::create('touches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->char('subject_hash', 64);
            $table->string('reference', 64);
            $table->timestampTz('delivered_at');

            $table->unique(['tenant_id', 'reference']);
            $table->index(['tenant_id', 'subject_hash', 'delivered_at']);
        });

        Schema::create('cost_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->string('kind', 20);
            // Micro-riyals (1 SAR = 1,000,000): a marketing message costs ~0.2159 SAR.
            $table->bigInteger('amount_micros');
            $table->string('reference', 64);
            $table->char('subject_hash', 64)->nullable();
            $table->jsonb('meta')->default('{}');
            $table->timestampTz('occurred_at');

            $table->unique(['tenant_id', 'kind', 'reference']);
            $table->index(['tenant_id', 'occurred_at']);
        });

        foreach (['experiment_assignments', 'exposures', 'touches', 'cost_ledger_entries'] as $table) {
            DB::unprepared("CREATE TRIGGER {$table}_append_only BEFORE UPDATE OR DELETE ON {$table} FOR EACH ROW EXECUTE FUNCTION codex_forbid_mutation();");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_ledger_entries');
        Schema::dropIfExists('touches');
        Schema::dropIfExists('exposures');
        Schema::dropIfExists('experiment_assignments');
        Schema::dropIfExists('experiments');
    }
};
