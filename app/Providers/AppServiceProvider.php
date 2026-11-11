<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blade::component('layouts.app', 'layout');

        // Application logs may contain personal data and credentials.
        // Only the primary administrator may inspect them in production.
        Gate::define('viewLogViewer', fn (?User $user): bool => (int) $user?->getKey() === 1);

        // One pagination style for all public pages using ->links().
        Paginator::defaultView('components.ui.pagination');
        Paginator::defaultSimpleView('components.ui.pagination');
    }
}
