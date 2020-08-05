<?php

namespace MailerSend\LaravelDriver;

use Illuminate\Support\ServiceProvider;

class LaravelDriverServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-driver.php'),
            ], 'config');
        }
    }

    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'mailersend-laravel-driver');

        // Register the main class to use with the facade
        $this->app->singleton('mailersend-laravel-driver', function () {
            return new LaravelDriver;
        });
    }
}
