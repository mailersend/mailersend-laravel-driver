<?php

namespace MailerSend\LaravelDriver\Tests;

use Illuminate\Support\Arr;
use MailerSend\Endpoints\Email;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\LaravelDriver\MailerSendTransport;
use MailerSend\MailerSend;
use Mockery\MockInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Part\DataPart;

class MailerSendTransportTest extends TestCase
{
    protected MailerSend $mailersend;
    protected MailerSendTransport $transport;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailersend = new MailerSend([
            'api_key' => 'key',
            'host' => '',
            'protocol' => '',
            'api_path' => '',
        ]);


        $this->transport = new MailerSendTransport($this->mailersend);
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
            $mock->expects('setSubject')->withArgs(['Subject'])->andReturnSelf();
            $mock->expects('setText')->withArgs(['Here is the text message'])->andReturnSelf();
        });

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->mailersend->email = $this->mock(Email::class,
            function (MockInterface $mock) use ($emailParams, $response) {
                $mock->allows('send')->withArgs([$emailParams])->andReturn($response);
            });

        $message = (new \Symfony\Component\Mime\Email())
            ->subject('Subject')
            ->from('John Doe <test@mailersend.com>')
            ->to('test-receive@mailersend.com')
            ->text('Here is the text message');

        $transport = new MailerSendTransport($this->mailersend);
        $sentMessage = $transport->send($message, Envelope::create($message));

        self::assertNotNull($sentMessage);

        $sentMessageString = $sentMessage->getMessage()->toString();

        self::assertStringContainsString('X-MailerSend-Message-Id: messageId', $sentMessageString);
        self::assertStringContainsString('X-MailerSend-Body: {"json":"value"}', $sentMessageString);
    }

    public function test_get_from(): void
    {
        $message = (new \Symfony\Component\Mime\Email())
            ->from('John Doe <test@mailersend.com>');

        $getFrom = $this->callMethod($this->transport, 'getFrom', [$message]);

        self::assertEquals(['email' => 'test@mailersend.com', 'name' => 'John Doe'], $getFrom);
    }

    public function test_get_reply_to(): void
    {
        $message = (new \Symfony\Component\Mime\Email())
            ->replyTo('John Doe <test@mailersend.com>');

        $getReplyTo = $this->callMethod($this->transport, 'getReplyTo', [$message]);

        self::assertEquals(['email' => 'test@mailersend.com', 'name' => 'John Doe'], $getReplyTo);
    }

    public function test_get_recipients(): void
    {
        $message = (new \Symfony\Component\Mime\Email())
            ->to('test-receive@mailersend.com');

        $getTo = $this->callMethod($this->transport, 'getRecipients', ['to', $message]);

        self::assertEquals('test-receive@mailersend.com', Arr::get(reset($getTo)->toArray(),
            'email'));
    }

    public function test_get_attachments(): void
    {
        $attachment = new DataPart('data', 'filename', 'image/jpeg');

        if (method_exists(new \Symfony\Component\Mime\Email(), 'attachPart')) {
            $message = (new \Symfony\Component\Mime\Email())
                ->attachPart($attachment);
        } else {
            $message = (new \Symfony\Component\Mime\Email())
                ->addPart($attachment);
        }

        $getAttachments = $this->callMethod($this->transport, 'getAttachments', [$message]);

        $attachmentResult = reset($getAttachments)->toArray();

        self::assertEquals('data', Arr::get($attachmentResult,
            'content'));
        self::assertEquals('filename', Arr::get($attachmentResult,
            'filename'));
        self::assertEquals('attachment', Arr::get($attachmentResult,
            'disposition'));
    }

    public function test_get_additional_data(): void
    {
        if (method_exists(new \Symfony\Component\Mime\Email(), 'attachPart')) {
            $message = (new \Symfony\Component\Mime\Email())
                ->attachPart(new DataPart(
                    json_encode([
                        'template_id' => 'id'
                    ], JSON_THROW_ON_ERROR),
                    MailerSendTransport::MAILERSEND_DATA_SUBTYPE.'.json',
                    MailerSendTransport::MAILERSEND_DATA_TYPE.'/'.MailerSendTransport::MAILERSEND_DATA_SUBTYPE
                ));
        } else {
            $message = (new \Symfony\Component\Mime\Email())
                ->addPart(new DataPart(
                    json_encode([
                        'template_id' => 'id'
                    ], JSON_THROW_ON_ERROR),
                    MailerSendTransport::MAILERSEND_DATA_SUBTYPE.'.json',
                    MailerSendTransport::MAILERSEND_DATA_TYPE.'/'.MailerSendTransport::MAILERSEND_DATA_SUBTYPE
                ));
        }


        $getAdditionalData = $this->callMethod($this->transport, 'getAdditionalData', [$message]);

        self::assertEquals('id', Arr::get($getAdditionalData,
            'template_id'));
        self::assertEquals([], Arr::get($getAdditionalData,
            'tags'));
        self::assertEquals([], Arr::get($getAdditionalData,
            'personalization'));
    }
}
