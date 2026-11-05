<?php

namespace MailerSend\LaravelDriver\Tests;

use Illuminate\Mail\MailManager;
use MailerSend\LaravelDriver\MailerSendTransport;
use MailerSend\MailerSend;
use ReflectionProperty;

class LaravelDriverServiceProviderTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('mail.mailers.mailersend', ['transport' => 'mailersend']);
        $app['config']->set('mailersend-driver.api_key', 'key');
    }

    public function test_requests_use_the_default_api_version(): void
    {
        self::assertSame('2026-10', $this->sdkOptions()['api_path']);
    }

    public function test_the_configured_api_version_is_forwarded(): void
    {
        config(['mailersend-driver.api_version' => 'v1']);

        self::assertSame('v1', $this->sdkOptions()['api_path']);
    }

    public function test_a_configured_api_path_wins_over_the_api_version(): void
    {
        config(['mailersend-driver.api_version' => '2027-01', 'mailersend-driver.api_path' => 'api/v1']);

        self::assertSame('api/v1', $this->sdkOptions()['api_path']);
    }

    private function sdkOptions(): array
    {
        /** @var MailerSendTransport $transport */
        $transport = $this->app->make(MailManager::class)->createSymfonyTransport(['transport' => 'mailersend']);

        $mailersend = (new ReflectionProperty(MailerSendTransport::class, 'mailersend'))->getValue($transport);

        return (new ReflectionProperty(MailerSend::class, 'options'))->getValue($mailersend);
    }
}
