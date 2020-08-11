<?php

namespace MailerSend\LaravelDriver\Tests;

use MailerSend\LaravelDriver\LaravelDriverServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelDriverServiceProvider::class];
    }

    /**
     * @throws \RuntimeException
     * @throws \ReflectionException
     */
    protected function callMethod($object, string $method, array $parameters = [])
    {
        try {
            $className = get_class($object);
            $reflection = new \ReflectionClass($className);
        } catch (\ReflectionException $e) {
            throw new \RuntimeException($e->getMessage());
        }

        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
