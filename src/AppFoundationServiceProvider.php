<?php

namespace TakahiroNago\AppFoundation;

use Illuminate\Support\ServiceProvider;
use TakahiroNago\AppFoundation\Console\PromoteUserToAdmin;
use TakahiroNago\AppFoundation\Services\GuestTrialCreditService;

class AppFoundationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/app-foundation.php', 'app-foundation');

        $this->app->bind(GuestTrialCreditService::class, fn (): GuestTrialCreditService => new GuestTrialCreditService(
            (int) config('app-foundation.guest_trial.credit_limit'),
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([PromoteUserToAdmin::class]);

            $this->publishes([
                __DIR__.'/../config/app-foundation.php' => config_path('app-foundation.php'),
            ], 'app-foundation-config');
        }
    }
}
