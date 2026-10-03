<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Scoped: one instance per request or queued job, so a long-running
        // queue worker never keeps settings memoised across jobs.
        $this->app->scoped(SettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        Paginator::defaultView('vendor.pagination.tamakkun');

        // Admin-editable branding (settings table) for the views that show it.
        View::composer(['components.brand', 'layouts.partials.head', 'layouts.guest', 'layouts.public', 'public.*', 'welcome', 'components.mail.layout'], function ($view): void {
            $settings = $this->app->make(SettingsService::class);
            $view->with([
                'platformName' => $settings->get('platform_name') ?: config('tamakkun.settings.platform_name'),
                'platformTagline' => $settings->get('tagline'),
            ]);
        });
    }

    private function configureRateLimiting(): void
    {
        // Per-IP ceiling on login requests. LoginRequest additionally locks
        // a single username/IP pair after 5 failed attempts.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(20)->by($request->ip()));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
