<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M02 internal commerce model (ADR-011). Our own shape, not Salla's payloads.
 * Every tenant-owned table carries tenant_id and every index starts with it.
 * Money is in halalas; phones are encrypted, with a per-tenant hash for lookups.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('provider', 20);
            $table->string('external_store_id', 64);
            $table->string('store_name')->nullable();
            $table->string('owner_email')->nullable();
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestampTz('access_expires_at')->nullable();
            $table->string('scope')->nullable();
            $table->string('status', 20);
            $table->timestampTz('last_webhook_at')->nullable();
            $table->timestampTz('last_reconciled_at')->nullable();
            // Events found by polling that never arrived as a webhook (P1 exit #8).
            $table->unsignedInteger('reconciliation_gap_count')->default(0);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'provider']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('external_id', 64);
            $table->string('name');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'external_id']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('external_id', 64);
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('category_external_id', 64)->nullable();
            $table->bigInteger('price_halalas')->nullable();
            $table->integer('quantity')->nullable();
            $table->string('status', 20)->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'external_id']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('external_id', 64)->nullable();
            $table->char('phone_hash', 64)->nullable();
            $table->text('phone')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'external_id']);
            $table->index(['tenant_id', 'phone_hash']);
        });

        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('external_id', 64);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->char('phone_hash', 64)->nullable();
            $table->bigInteger('total_halalas')->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->integer('item_count')->default(0);
            $table->text('checkout_url')->nullable();
            $table->string('status', 20);
            $table->timestampTz('abandoned_at')->nullable();
            $table->timestampTz('purchased_at')->nullable();
            $table->string('discovered_by', 20);
            $table->timestampsTz();

            $table->unique(['tenant_id', 'external_id']);
            $table->index(['tenant_id', 'phone_hash']);
            $table->index(['tenant_id', 'status', 'abandoned_at']);
        });

        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('cart_id')->constrained('carts')->cascadeOnDelete();
            $table->string('product_external_id', 64)->nullable();
            $table->string('name')->nullable();
            $table->integer('quantity');
            $table->bigInteger('unit_price_halalas')->default(0);

            $table->index(['tenant_id', 'cart_id']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->string('external_id', 64);
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->char('phone_hash', 64)->nullable();
            $table->bigInteger('total_halalas')->default(0);
            $table->bigInteger('discount_halalas')->default(0);
            $table->bigInteger('shipping_halalas')->default(0);
            $table->bigInteger('refunded_halalas')->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->string('status', 40)->nullable();
            $table->string('payment_method', 40)->nullable();
            $table->string('coupon_code', 64)->nullable();
            $table->timestampTz('placed_at');
            $table->timestampTz('cancelled_at')->nullable();
            $table->timestampsTz();

            $table->unique(['tenant_id', 'external_id']);
            $table->index(['tenant_id', 'phone_hash', 'placed_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('product_external_id', 64)->nullable();
            $table->string('category_external_id', 64)->nullable();
            $table->integer('quantity');
            $table->bigInteger('unit_price_halalas')->default(0);

            $table->index(['tenant_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('carts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('store_connections');
    }
};
