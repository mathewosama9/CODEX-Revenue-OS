<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M11 usage metering from day one (E13a), even before we bill on it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('metric', 50);
            $table->date('day');
            $table->unsignedBigInteger('count')->default(0);

            $table->unique(['tenant_id', 'metric', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_daily');
    }
};
