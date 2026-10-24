<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * E08: approximate gross margin per category, versioned by effective_from so a
 * change never rewrites past reports. category_external_id null = store default.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_margins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('category_external_id', 64)->nullable();
            $table->unsignedSmallInteger('margin_bps');
            $table->timestampTz('effective_from');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['tenant_id', 'category_external_id', 'effective_from']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_margins');
    }
};
