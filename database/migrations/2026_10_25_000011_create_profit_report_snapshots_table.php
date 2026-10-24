<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Weekly report snapshots (E07): a report a merchant saw never changes later;
 * corrections appear in the next snapshot.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profit_report_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->restrictOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('method_version', 10);
            $table->jsonb('payload');
            $table->timestampTz('computed_at')->useCurrent();

            $table->index(['tenant_id', 'period_end']);
        });

        DB::unprepared('CREATE TRIGGER profit_report_snapshots_append_only BEFORE UPDATE OR DELETE ON profit_report_snapshots FOR EACH ROW EXECUTE FUNCTION codex_forbid_mutation();');
    }

    public function down(): void
    {
        Schema::dropIfExists('profit_report_snapshots');
    }
};
