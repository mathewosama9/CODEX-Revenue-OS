<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Proof behind a merchant's consent import (review B1). The CSV itself is
 * never stored (it is a list of phone numbers); its SHA-256 is, with the exact
 * text customers agreed to and who attested it. Append-only, like the ledger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consent_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            // Written into every consent record's evidence, before this row exists.
            $table->ulid('reference')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->char('file_sha256', 64);
            $table->text('consent_text');
            $table->char('consent_text_sha256', 64);
            $table->string('collected_via', 30);
            $table->string('attestation_version', 20);
            $table->unsignedInteger('rows_total');
            $table->unsignedInteger('granted');
            $table->unsignedInteger('already_granted');
            $table->unsignedInteger('skipped_opted_out');
            $table->unsignedInteger('skipped_invalid');
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['tenant_id', 'id']);
        });

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER consent_imports_append_only
                BEFORE UPDATE OR DELETE ON consent_imports
                FOR EACH ROW EXECUTE FUNCTION codex_forbid_mutation();
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('consent_imports');
    }
};
