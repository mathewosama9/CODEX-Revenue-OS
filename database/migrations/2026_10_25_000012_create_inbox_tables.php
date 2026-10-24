<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M04 Handoff Inbox lite (E12a). Coexistence keeps staff replying from the
 * WhatsApp Business app; here they see who needs a human, assign and note.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->char('subject_hash', 64);
            $table->string('status', 10);
            $table->string('reason', 30);
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('last_message_at');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'subject_hash']);
            $table->index(['tenant_id', 'status', 'last_message_at']);
        });

        Schema::create('conversation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestampsTz();

            $table->index(['tenant_id', 'conversation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_notes');
        Schema::dropIfExists('conversations');
    }
};
