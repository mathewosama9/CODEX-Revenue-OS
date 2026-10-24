<?php

namespace App\Providers;

use App\Modules\Commerce\CommerceAdapter;
use App\Modules\Commerce\Salla\SallaAdapter;
use App\Modules\Commerce\Salla\SallaClient;
use App\Modules\Compliance\QuietHours;
use App\Shared\Phone\SubjectHasher;
use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Scoped: reset between requests and between queued jobs, so a tenant never leaks across them.
        $this->app->scoped(TenantContext::class);

        $this->app->singleton(SubjectHasher::class, fn () => new SubjectHasher(
            (string) config('codex.subject_hash_key'),
        ));

        $this->app->singleton(SallaClient::class, fn () => new SallaClient(
            (string) config('services.salla.api_base'),
            (string) config('services.salla.accounts_base'),
            (string) config('services.salla.client_id'),
            (string) config('services.salla.client_secret'),
        ));

        // Salla is the only commerce platform until Zid (P4, ADR-011).
        $this->app->bind(CommerceAdapter::class, SallaAdapter::class);

        $this->app->singleton(QuietHours::class, fn () => new QuietHours(
            config('codex.quiet_hours.timezone'),
            config('codex.quiet_hours.default'),
            config('codex.quiet_hours.ramadan'),
            config('codex.quiet_hours.ramadan_periods'),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        RateLimiter::for('webhooks', fn (Request $request) => Limit::perMinute(600)->by($request->ip()));

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
