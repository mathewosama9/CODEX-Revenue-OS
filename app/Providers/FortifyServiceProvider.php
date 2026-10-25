<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fortify's own responses: JSON for the Next.js app, and a redirect to
        // config('fortify.home') (the app) for the rare plain browser request.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureActions();
        $this->configureRateLimiting();
        $this->configureEmailLinks();
    }

    /**
     * Reset links open the Next.js app (ADR-002), in the language of the request.
     */
    private function configureEmailLinks(): void
    {
        ResetPassword::createUrlUsing(fn ($user, string $token) => sprintf(
            '%s/%s/reset-password?%s',
            config('app.frontend_url'),
            app()->getLocale() === 'en' ? 'en' : 'ar',
            http_build_query(['token' => $token, 'email' => $user->getEmailForPasswordReset()]),
        ));
    }

    /**
     * Configure Fortify actions.
     */
    private function configureActions(): void
    {
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::createUsersUsing(CreateNewUser::class);
    }

    /**
     * Configure rate limiting.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

    }
}
