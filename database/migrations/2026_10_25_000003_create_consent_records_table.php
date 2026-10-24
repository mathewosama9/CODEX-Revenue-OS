<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M10 consent ledger (E04). Append-only: the current state of a subject is
 * its latest record. The phone itself is never stored here, only its
 * per-tenant hash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->char('subject_hash', 64);
            $table->string('channel', 20);
            $table->string('purpose', 20);
            $table->string('status', 10);
            $table->string('source', 30);
            $table->jsonb('evidence')->default('{}');
            $table->timestampTz('recorded_at')->useCurrent();

            $table->index(['tenant_id', 'subject_hash', 'channel', 'purpose', 'id']);
        });

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER consent_records_append_only
                BEFORE UPDATE OR DELETE ON consent_records
                FOR EACH ROW EXECUTE FUNCTION codex_forbid_mutation();
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_records');
    }
};
