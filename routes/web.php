<?php

use Illuminate\Support\Facades\Route;

/*
| Laravel is the API (ADR-002). Merchants use the Next.js app; anyone landing
| here is sent there. Fortify's JSON routes, /api, /webhooks and the Filament
| admin (/admin) are registered elsewhere.
*/

Route::redirect('/', config('app.frontend_url'))->name('home');
