<?php

use App\Modules\Commerce\Salla\SallaWebhookController;
use App\Modules\Messaging\Meta\MetaWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Inbound webhooks. Loaded without the web middleware (no session, cookies or
| CSRF): providers authenticate with signatures, verified in each controller.
*/

Route::post('webhooks/salla', SallaWebhookController::class)->name('webhooks.salla');

Route::get('webhooks/meta', [MetaWebhookController::class, 'verify'])->name('webhooks.meta.verify');
Route::post('webhooks/meta', [MetaWebhookController::class, 'receive'])->name('webhooks.meta');
