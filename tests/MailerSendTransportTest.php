<?php

namespace MailerSend\LaravelDriver\Tests;

use MailerSend\Endpoints\Email;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\LaravelDriver\LaravelDriverServiceProvider;
use MailerSend\LaravelDriver\MailerSendTransport;
use MailerSend\MailerSend;
use Mockery\MockInterface;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Message;
use Swift_Mime_SimpleMessage;

class MailerSendTransportTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [LaravelDriverServiceProvider::class];
    }

    public function test_basic_message_is_sent(): void
    {
        $response = [
            'response' => $this->mock(ResponseInterface::class, function (MockInterface $mock) {
                $mock->expects('getHeaderLine')->withArgs(['X-Message-Id'])->andReturn('messageId');

                $stream = $this->mock(StreamInterface::class, function (MockInterface $mock) {
                    $mock->expects('getContents')->withNoArgs()->andReturn('{"json":"value"}');
                });

                $mock->expects('getBody')->withNoArgs()->andReturn($stream);
            }),
        ];

        $emailParams = $this->partialMock(EmailParams::class, function (MockInterface $mock) {
            $mock->expects('setFrom')->withArgs(['test@mailersend.com'])->andReturnSelf();
            $mock->expects('setFromName')->withArgs(['John Doe'])->andReturnSelf();
            $mock->expects('setRecipients')->withAnyArgs()->andReturnSelf();
            $mock->expects('setSubject')->withArgs(['Wonderful Subject'])->andReturnSelf();
            $mock->expects('setText')->withArgs(['Here is the message itself'])->andReturnSelf();
        });

        $mailersend = new MailerSend([
            'api_key' => 'key',
            'host' => '',
            'protocol' => '',
            'api_path' => '',
        ]);

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $mailersend->email = $this->mock(Email::class, function (MockInterface $mock) use ($emailParams, $response) {
            $mock->allows('send')->withArgs([$emailParams])->andReturn($response);
        });

        $message = (new Swift_Message('Wonderful Subject'))
            ->setFrom(['test@mailersend.com' => 'John Doe'])
            ->setTo(['test-receive@mailersend.com'])
            ->setBody('Here is the message itself');

        $sendPerformed = new class implements Swift_Events_EventListener {
            public ?Swift_Mime_SimpleMessage $messageAfterSend;

            public function sendPerformed(Swift_Events_SendEvent $event): void
            {
                $this->messageAfterSend = $event->getMessage();
            }
        };

        $transport = new MailerSendTransport($mailersend);
        $transport->registerPlugin($sendPerformed);
        $recipients = $transport->send($message);

        self::assertEquals(1, $recipients);

        self::assertEquals('messageId', $sendPerformed->messageAfterSend
            ->getHeaders()
            ->get('X-MailerSend-Message-Id')
            ->getFieldBody());

        self::assertEquals('{"json":"value"}', $sendPerformed->messageAfterSend
            ->getHeaders()
            ->get('X-MailerSend-Body')
            ->getFieldBody());
    }
}
