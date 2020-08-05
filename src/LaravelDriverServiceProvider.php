<?php

namespace MailerSend\LaravelDriver;

use Illuminate\Mail\MailManager;
use Illuminate\Support\ServiceProvider;

class LaravelDriverServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->make(MailManager::class)->extend('mailersend', function () {
            $config = $this->app['config']->get('mailersend-driver', []);

            return new MailerSendTransport($config);
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('laravel-driver.php'),
            ], 'config');
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/mailersend-driver.php', 'mailersend-driver');
    }
}
