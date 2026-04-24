<?php

namespace Webteractive\Mailulator;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webteractive\Mailulator\Console\InstallCommand;
use Webteractive\Mailulator\Driver\MailulatorTransport;
use Webteractive\Mailulator\Http\Middleware\Authenticate;
use Webteractive\Mailulator\Http\Middleware\EnsureValidInboxToken;
use Webteractive\Mailulator\Jobs\PruneEmails;

class MailulatorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mailulator.php', 'mailulator');

        if (config('mailulator.receiver.enabled')) {
            $this->registerMailulatorConnection();
        }
    }

    public function boot(): void
    {
        $this->registerRateLimiter();
        $this->registerRoutes();
        $this->registerMigrations();
        $this->registerResources();
        $this->registerCommands();
        $this->registerPublishing();
        $this->registerDriverTransport();
        $this->registerSchedule();
    }

    protected function registerSchedule(): void
    {
        if (! config('mailulator.receiver.enabled')) {
            return;
        }

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->job(new PruneEmails)->daily()->name('mailulator:prune')->onOneServer();
        });
    }

    protected function registerMailulatorConnection(): void
    {
        if (config()->has('database.connections.mailulator')) {
            return;
        }

        $config = config('mailulator.receiver.database');
        $connection = $config['connection'] ?? 'sqlite';

        if ($connection === 'sqlite') {
            $path = $config['sqlite_path'] ?: database_path('mailulator.sqlite');

            if (! file_exists($path)) {
                @mkdir(dirname($path), 0755, true);
                @touch($path);
            }

            config()->set('database.connections.mailulator', [
                'driver' => 'sqlite',
                'database' => $path,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ]);

            return;
        }

        config()->set('database.connections.mailulator', [
            'driver' => $connection,
            'host' => $config['host'],
            'port' => $config['port'],
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'],
            'charset' => $config['charset'] ?? 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ]);
    }

    protected function registerRateLimiter(): void
    {
        if (! config('mailulator.receiver.enabled')) {
            return;
        }

        $maxAttempts = (int) config('mailulator.receiver.rate_limit.max_attempts', 600);

        RateLimiter::for('mailulator-ingest', function (Request $request) use ($maxAttempts) {
            $inbox = app()->bound('mailulator.inbox') ? app('mailulator.inbox') : null;

            return Limit::perMinute($maxAttempts)
                ->by($inbox?->getKey() ?: $request->ip());
        });
    }

    protected function registerRoutes(): void
    {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        if (! config('mailulator.receiver.enabled')) {
            return;
        }

        Route::group([
            'prefix' => 'api',
            'middleware' => ['api', EnsureValidInboxToken::class, 'throttle:mailulator-ingest'],
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/ingest.php');
        });

        Route::group([
            'prefix' => config('mailulator.receiver.ui.path', 'mailulator'),
            'domain' => config('mailulator.receiver.ui.domain'),
            'middleware' => array_merge(
                (array) config('mailulator.middleware', ['web']),
                [Authenticate::class],
            ),
        ], function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        $this->registerBroadcastChannels();
    }

    protected function registerBroadcastChannels(): void
    {
        $realtime = config('mailulator.receiver.realtime', []);

        if (! ($realtime['enabled'] ?? true)) {
            return;
        }

        if (($realtime['mode'] ?? 'polling') !== 'broadcast') {
            return;
        }

        require __DIR__.'/../routes/channels.php';
    }

    protected function registerMigrations(): void
    {
        if (! config('mailulator.receiver.enabled')) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function registerResources(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'mailulator');
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
        ]);
    }

    protected function registerPublishing(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../stubs/MailulatorServiceProvider.stub' => app_path('Providers/MailulatorServiceProvider.php'),
        ], 'mailulator-provider');

        $this->publishes([
            __DIR__.'/../config/mailulator.php' => config_path('mailulator.php'),
        ], 'mailulator-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'mailulator-migrations');

        $this->publishes([
            __DIR__.'/../dist' => public_path('vendor/mailulator'),
        ], ['mailulator-assets', 'laravel-assets']);
    }

    protected function registerDriverTransport(): void
    {
        if (! config('mailulator.driver.enabled')) {
            return;
        }

        Mail::extend('mailulator', function (array $config = []) {
            return new MailulatorTransport(
                url: (string) config('mailulator.driver.url'),
                token: (string) config('mailulator.driver.token'),
                timeout: (int) config('mailulator.driver.timeout', 5),
                onFailure: (string) config('mailulator.driver.on_failure', 'log'),
            );
        });
    }
}
