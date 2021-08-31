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
use Swift_Events_EventListener;
use Swift_Events_SendEvent;
use Swift_Message;
use Swift_Mime_SimpleMessage;

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

        $message = (new Swift_Message('Subject'))
            ->setFrom(['test@mailersend.com' => 'John Doe'])
            ->setTo(['test-receive@mailersend.com'])
            ->setBody('Here is the text message');

        $sendPerformed = new class implements Swift_Events_EventListener {
            public ?Swift_Mime_SimpleMessage $messageAfterSend;

            public function sendPerformed(Swift_Events_SendEvent $event): void
            {
                $this->messageAfterSend = $event->getMessage();
            }
        };

        $transport = new MailerSendTransport($this->mailersend);
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

    public function test_get_from(): void
    {
        $message = (new Swift_Message(''))
            ->setFrom(['test@mailersend.com' => 'John Doe']);

        $getFrom = $this->callMethod($this->transport, 'getFrom', [$message]);

        self::assertEquals(['email' => 'test@mailersend.com', 'name' => 'John Doe'], $getFrom);
    }

    public function test_get_reply_to(): void
    {
        $message = (new Swift_Message(''))
            ->setReplyTo(['test@mailersend.com' => 'John Doe']);

        $getReplyTo = $this->callMethod($this->transport, 'getReplyTo', [$message]);

        self::assertEquals(['email' => 'test@mailersend.com', 'name' => 'John Doe'], $getReplyTo);
    }

    public function test_get_to(): void
    {
        $message = (new Swift_Message(''))
            ->setTo(['test-receive@mailersend.com']);

        $getTo = $this->callMethod($this->transport, 'getTo', [$message]);

        self::assertEquals('test-receive@mailersend.com', Arr::get(reset($getTo)->toArray(),
            'email'));
    }

    public function test_get_contents(): void
    {
        $message = (new Swift_Message(''))
            ->setBody('HTML', 'text/html')
            ->addPart('Text', 'text/plain');

        $getContents = $this->callMethod($this->transport, 'getContents', [$message]);

        self::assertEquals(['text' => 'Text', 'html' => 'HTML'], $getContents);

        $message = (new Swift_Message(''));

        $getContents = $this->callMethod($this->transport, 'getContents', [$message]);

        self::assertEquals(['text' => '', 'html' => ''], $getContents);

        $message = (new Swift_Message('', 'Text'));

        $getContents = $this->callMethod($this->transport, 'getContents', [$message]);

        self::assertEquals(['text' => 'Text', 'html' => ''], $getContents);

        $message = (new Swift_Message('', 'HTML', 'text/html'));

        $getContents = $this->callMethod($this->transport, 'getContents', [$message]);

        self::assertEquals(['text' => '', 'html' => 'HTML'], $getContents);
    }

    public function test_get_attachments(): void
    {
        $attachment = new \Swift_Attachment('data', 'filename', 'image/jpeg');

        $message = (new Swift_Message(''))
            ->attach($attachment);

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
        $message = (new Swift_Message(''))
            ->addPart(json_encode([
                'template_id' => 'id'
            ], JSON_THROW_ON_ERROR), MailerSendTransport::MAILERSEND_DATA);

        $getAdditionalData = $this->callMethod($this->transport, 'getAdditionalData', [$message]);

        self::assertEquals('id', Arr::get($getAdditionalData,
            'template_id'));
        self::assertEquals([], Arr::get($getAdditionalData,
            'variables'));
        self::assertEquals([], Arr::get($getAdditionalData,
            'tags'));
        self::assertEquals([], Arr::get($getAdditionalData,
            'personalization'));
    }
}
