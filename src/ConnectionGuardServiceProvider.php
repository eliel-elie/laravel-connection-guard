<?php

namespace Elielelie\ConnectionGuard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class ConnectionGuardServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/connection-guard.php', 'connection-guard'
        );

        $this->app->singleton(ConnectionGuardManager::class, function ($app) {
            return new ConnectionGuardManager($app);
        });

        $this->app->alias(ConnectionGuardManager::class, 'connection-guard');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/connection-guard.php' => config_path('connection-guard.php'),
            ], 'connection-guard-config');
        }

        $this->app->extend('db', function ($db, $app) {
            return new ConnectionGuardDatabaseManager($app, $app['db.factory']);
        });

        if (class_exists(Model::class)) {
            Model::setConnectionResolver($this->app['db']);
        }
    }
}

