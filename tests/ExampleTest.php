<?php

namespace MailerSend\LaravelDriver\Tests;

use Orchestra\Testbench\TestCase;
use MailerSend\LaravelDriver\LaravelDriverServiceProvider;

class ExampleTest extends TestCase
{

    protected function getPackageProviders($app)
    {
        return [LaravelDriverServiceProvider::class];
    }
    
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }
}
