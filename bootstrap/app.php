<?php

use App\Http\Middleware\SetRequestLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('throttle:webhooks')->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Error messages in the merchant's language (Accept-Language from web/).
        $middleware->append(SetRequestLocale::class);

        // Sanctum SPA auth for the Next.js frontend (ADR-002).
        $middleware->statefulApi();

        // Laravel renders no merchant pages: a signed-out browser goes to the app's sign-in.
        $middleware->redirectGuestsTo(fn () => config('app.frontend_url').'/ar/login');
    })
    ->withCommands([__DIR__.'/../app/Modules/Platform/Console', __DIR__.'/../app/Modules/SalesAgent/Console', __DIR__.'/../app/Modules/Messaging/Console'])
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
