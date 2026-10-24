<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M03 WhatsApp (E03). One WABA + business portfolio per merchant; Meta fees go
 * on the merchant's card. Message bodies are PII: encrypted, short retention.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('waba_id', 64);
            $table->text('access_token');
            $table->string('status', 20);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'waba_id']);
        });

        Schema::create('whatsapp_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('phone_number_id', 64);
            $table->string('display_phone_number', 32)->nullable();
            $table->string('quality_rating', 10)->default('UNKNOWN');
            $table->string('status', 20);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'phone_number_id']);
        });

        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('external_id', 64)->nullable();
            $table->string('name', 512);
            $table->string('language', 15);
            $table->string('category', 20);
            $table->string('status', 20);
            // What the template is for in our flows (cart_recovery_1, ...); null = unused.
            $table->string('purpose', 40)->nullable();
            $table->jsonb('components')->default('[]');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'name', 'language']);
            $table->index(['tenant_id', 'purpose']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('whatsapp_number_id')->nullable()->constrained('whatsapp_numbers')->nullOnDelete();
            $table->string('direction', 10);
            $table->string('wamid', 128)->nullable();
            $table->string('idempotency_key', 128)->nullable();
            $table->char('subject_hash', 64)->nullable();
            $table->string('template_name', 512)->nullable();
            $table->string('category', 20)->nullable();
            $table->string('status', 20);
            $table->string('error_code', 20)->nullable();
            $table->string('error_title')->nullable();
            $table->text('body')->nullable();
            $table->jsonb('context')->default('{}');
            $table->string('pricing_category', 30)->nullable();
            $table->boolean('billable')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->timestampTz('read_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'wamid']);
            $table->unique(['tenant_id', 'idempotency_key']);
            $table->index(['tenant_id', 'subject_hash', 'created_at']);
            $table->index(['tenant_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_templates');
        Schema::dropIfExists('whatsapp_numbers');
        Schema::dropIfExists('whatsapp_accounts');
    }
};
