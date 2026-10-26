<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * ADR-012: AgentSettings used to store the default hand-off threshold
 * (150,000 halalas = 1,500 SAR) in every row. Null now means "the dollar
 * default, converted to the workspace currency", which is exactly 150,000
 * for SAR. Without this, a workspace that later takes another currency from
 * its store (e.g. EGP) would read 150,000 as 1,500 pounds.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('agent_settings')
            ->where('handoff_cart_halalas', 150_000)
            ->whereIn('tenant_id', DB::table('teams')->where('currency', 'SAR')->select('id'))
            ->update(['handoff_cart_halalas' => null]);
    }

    public function down(): void
    {
        // Null reads as the same 150,000 halalas in a SAR workspace: nothing to restore.
    }
};
