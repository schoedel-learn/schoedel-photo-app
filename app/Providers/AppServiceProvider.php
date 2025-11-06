<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Register policies (auto-discovery should work, but explicit registration for clarity)
        // Policies are auto-discovered if they follow App\Policies\{ModelName}Policy naming
    }
}
