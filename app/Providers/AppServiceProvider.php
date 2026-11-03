<?php

namespace App\Providers;

use App\Policies\ReceptionDeskPolicy;
use App\Support\ClinicIdentity;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ClinicIdentity::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy('reception', ReceptionDeskPolicy::class);

        View::composer('*', function ($view): void {
            if (! isset($view->clinicIdentity)) {
                $view->with('clinicIdentity', app(ClinicIdentity::class));
            }
        });
    }
}
