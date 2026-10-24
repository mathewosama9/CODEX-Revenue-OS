<?php

namespace App\Providers;

use App\Modules\Commerce\Commerce;
use App\Modules\Commerce\CommerceAdapter;
use App\Modules\Commerce\Salla\SallaAdapter;
use App\Modules\Commerce\Salla\SallaClient;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Compliance\QuietHours;
use App\Modules\Measurement\CostLedger;
use App\Modules\Measurement\ExperimentAssigner;
use App\Modules\Measurement\MarginLookup;
use App\Modules\Measurement\ProfitReport;
use App\Modules\Messaging\Events\MessageDelivered;
use App\Modules\Messaging\Listeners\BookDeliveryCost;
use App\Modules\Messaging\Meta\MetaGraphClient;
use App\Modules\Recovery\Listeners\RecoveryListeners;
use App\Shared\Phone\SubjectHasher;
use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
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

        $this->app->singleton(MetaGraphClient::class, fn () => new MetaGraphClient(
            (string) config('services.meta.graph_base'),
            (string) config('services.meta.app_id'),
            (string) config('services.meta.app_secret'),
        ));

        // Salla is the only commerce platform until Zid (P4, ADR-011).
        $this->app->bind(CommerceAdapter::class, SallaAdapter::class);

        $this->app->bind(MarginLookup::class, fn () => new MarginLookup(
            (int) config('codex.assumed_default_margin_bps'),
        ));

        $this->app->bind(ExperimentAssigner::class, fn ($app) => new ExperimentAssigner(
            (int) config('codex.measurement.default_holdout_bps'),
            $app->make(AuditLogger::class),
        ));

        $this->app->bind(CostLedger::class, fn () => new CostLedger(
            (array) config('codex.meta_rates_usd'),
            (float) config('codex.usd_to_sar'),
        ));

        $this->app->bind(ProfitReport::class, fn ($app) => new ProfitReport(
            $app->make(ExperimentAssigner::class),
            $app->make(Commerce::class),
            $app->make(MarginLookup::class),
            (int) config('codex.measurement.outcome_window_days'),
            (int) config('codex.measurement.attribution_window_hours'),
            (int) config('codex.measurement.mde_halalas_per_subject'),
            (string) config('codex.measurement.method_version'),
        ));

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
        $this->registerModuleListeners();
    }

    /**
     * Cross-module wiring: who reacts to which domain event (09 §9.8).
     */
    protected function registerModuleListeners(): void
    {
        Event::listen(MessageDelivered::class, BookDeliveryCost::class);
        Event::subscribe(RecoveryListeners::class);
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
