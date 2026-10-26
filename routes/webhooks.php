<?php

use App\Modules\Commerce\Salla\SallaWebhookController;
use App\Modules\Commerce\WooCommerce\WooWebhookController;
use App\Modules\Messaging\Meta\MetaWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Inbound webhooks. Loaded without the web middleware (no session, cookies or
| CSRF): providers authenticate with signatures, verified in each controller.
*/

Route::post('webhooks/salla', SallaWebhookController::class)->name('webhooks.salla');

// CODEX Connector (WordPress plugin): one URL per store, signed with that store's secret.
Route::post('webhooks/woocommerce/{storeId}', WooWebhookController::class)
    ->where('storeId', 'woo_[a-z0-9]{26}')
    ->name('webhooks.woocommerce');

Route::get('webhooks/meta', [MetaWebhookController::class, 'verify'])->name('webhooks.meta.verify');
Route::post('webhooks/meta', [MetaWebhookController::class, 'receive'])->name('webhooks.meta');
