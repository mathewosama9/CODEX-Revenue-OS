<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A data-subject deletion removes the conversation; the agent's runs and
 * drafts (the reply text, with names restored) go with it (review: security #4).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['agent_runs', 'reply_drafts'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->foreign('conversation_id')->references('id')->on('conversations')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (['agent_runs', 'reply_drafts'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropForeign(['conversation_id']));
        }
    }
};
