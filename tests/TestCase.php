<?php

namespace TakahiroNago\AppFoundation\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use TakahiroNago\AppFoundation\AppFoundationServiceProvider;
use TakahiroNago\AppFoundation\Http\Controllers\StripeWebhookController;
use TakahiroNago\AppFoundation\Http\Middleware\EnsureActiveUser;
use TakahiroNago\AppFoundation\Http\Middleware\EnsureAdmin;
use TakahiroNago\AppFoundation\Tests\Fixtures\User;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [AppFoundationServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('app-foundation.guest_trial.credit_limit', 2);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('password')->default('');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });

        // Same shape as the table the applications create.
        Schema::create('guest_trial_credits', function (Blueprint $table): void {
            $table->string('device_hash', 64)->primary();
            $table->unsignedInteger('used_credits')->default(0);
            $table->timestamps();
        });
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->group(function (): void {
            Route::get('/login', fn () => 'login')->name('login');
            Route::get('/members', fn () => 'members')->middleware(EnsureActiveUser::class);
            Route::get('/admin', fn () => 'admin')->middleware(EnsureAdmin::class);
        });

        Route::post('/stripe/webhook', StripeWebhookController::class);
    }
}
