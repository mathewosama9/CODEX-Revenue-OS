<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WooCommerce stores (trials): the site address, and the REST keys plus the
 * connector secret, encrypted together (docs/integrations/woocommerce-connector.md).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_connections', function (Blueprint $table) {
            $table->string('site_url')->nullable()->after('external_store_id');
            $table->text('credentials')->nullable()->after('refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('store_connections', function (Blueprint $table) {
            $table->dropColumn(['site_url', 'credentials']);
        });
    }
};
