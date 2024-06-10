<?php

namespace MailerSend\LaravelDriver;

use Illuminate\Mail\MailManager;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use MailerSend\MailerSend;

class LaravelDriverServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->make(MailManager::class)->extend('mailersend', function (array $config) {
            $config = array_merge($this->app['config']->get('mailersend-driver', []), $config);

            $mailersend = new MailerSend([
                'api_key' => Arr::get($config, 'api_key'),
                'host' => Arr::get($config, 'host'),
                'protocol' => Arr::get($config, 'protocol'),
                'api_path' => Arr::get($config, 'api_path'),
            ]);

            return new MailerSendTransport($mailersend);
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
