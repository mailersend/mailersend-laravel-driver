<?php

namespace MailerSend\LaravelDriver;

use Illuminate\Support\Facades\Facade;

class LaravelDriverFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mailersend-laravel-driver';
    }
}
