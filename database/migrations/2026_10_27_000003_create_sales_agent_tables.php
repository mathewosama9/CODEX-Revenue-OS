<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M06 Sales Agent (E11) and the rest of the inbox (E12b). Every AI reply is a
 * draft that a member approves (Assisted, 09 §9.11.3). Scoped Autonomous
 * exists but needs an admin flag, the owner's written approval and 4 weeks
 * of Assisted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained('teams')->cascadeOnDelete();
            $table->string('mode', 20)->default('off');
            $table->unsignedBigInteger('daily_cap_usd_micros');
            $table->bigInteger('handoff_cart_halalas')->nullable();
            $table->timestampTz('assisted_since')->nullable();
            $table->boolean('autonomy_allowed_by_admin')->default(false);
            $table->foreignId('autonomy_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('autonomy_approved_at')->nullable();
            $table->timestampTz('auto_downgraded_at')->nullable();
            $table->timestampsTz();
        });

        Schema::create('agent_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('inbound_message_id');
            $table->string('mode', 20);
            $table->string('status', 20);
            $table->string('handoff_reason', 30)->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('validator_blocks')->default(0);
            // Tool names and ids only, never message text (ADR-008).
            $table->jsonb('trace');
            $table->unsignedBigInteger('cost_usd_micros')->default(0);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'inbound_message_id']);
            $table->index(['tenant_id', 'conversation_id']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::create('reply_drafts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('agent_run_id')->constrained('agent_runs')->cascadeOnDelete();
            $table->unsignedBigInteger('conversation_id');
            $table->text('body');
            $table->text('final_body')->nullable();
            $table->string('status', 20);
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('decided_at')->nullable();
            $table->string('send_error', 60)->nullable();
            $table->timestampsTz();

            $table->index(['tenant_id', 'status', 'created_at']);
            $table->index(['tenant_id', 'conversation_id']);
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->text('phone')->nullable();
            $table->timestampTz('last_inbound_at')->nullable();
            $table->boolean('ai_paused')->default(false);
            $table->foreignId('ai_paused_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('ai_paused_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ai_paused_by');
            $table->dropColumn(['phone', 'last_inbound_at', 'ai_paused', 'ai_paused_at']);
        });
        Schema::dropIfExists('reply_drafts');
        Schema::dropIfExists('agent_runs');
        Schema::dropIfExists('agent_settings');
    }
};
