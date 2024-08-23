<?php

namespace MailerSend\LaravelDriver;

use MailerSend\Exceptions\MailerSendHttpException;
use MailerSend\Helpers\Builder\Attachment;
use MailerSend\Helpers\Builder\EmailParams;
use MailerSend\Helpers\Builder\Recipient;
use MailerSend\MailerSend;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\RawMessage;
use function json_decode;

class MailerSendTransport implements TransportInterface
{
    public const MAILERSEND_DATA_TYPE = 'text';
    public const MAILERSEND_DATA_SUBTYPE = 'mailersend-data';

    public const MAILERSEND_DATA_TEMPLATE_ID = 'template_id';
    public const MAILERSEND_DATA_TAGS = 'tags';
    public const MAILERSEND_DATA_PERSONALIZATION = 'personalization';
    public const MAILERSEND_DATA_PRECENDECE_BULK_HEADER = 'precedence_bulk_header';
    public const MAILERSEND_DATA_SEND_AT = 'send_at';

    protected MailerSend $mailersend;

    public function __construct(MailerSend $mailersend)
    {
        $this->mailersend = $mailersend;
    }

    /**
     * @throws \Assert\AssertionFailedException
     * @throws \JsonException
     * @throws \Psr\Http\Client\ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws \MailerSend\Exceptions\MailerSendAssertException
     */
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        try{
            ['email' => $fromEmail, 'name' => $fromName] = $this->getFrom($message);
            ['email' => $replyToEmail, 'name' => $replyToName] = $this->getReplyTo($message);

            $text = $message->getTextBody();
            $html = $message->getHtmlBody();

            $to = $this->getRecipients('to', $message);
            $cc = $this->getRecipients('cc', $message);
            $bcc = $this->getRecipients('bcc', $message);

            $subject = $message->getSubject();

            $attachments = $this->getAttachments($message);

            [
                'template_id' => $template_id,
                'tags' => $tags,
                'personalization' => $personalization,
                'precedence_bulk_header' => $precedenceBulkHeader,
                'send_at' => $sendAt,
            ] = $this->getAdditionalData($message);

            $emailParams = app(EmailParams::class)
                ->setFrom($fromEmail)
                ->setFromName($fromName)
                ->setReplyTo($replyToEmail)
                ->setReplyToName(strval($replyToName))
                ->setRecipients($to)
                ->setCc($cc)
                ->setBcc($bcc)
                ->setSubject($subject)
                ->setHtml($html)
                ->setText($text)
                ->setTemplateId($template_id)
                ->setPersonalization($personalization)
                ->setAttachments($attachments)
                ->setTags($tags)
                ->setPrecedenceBulkHeader($precedenceBulkHeader)
                ->setSendAt($sendAt);

            $response = $this->mailersend->email->send($emailParams);

            /** @var ResponseInterface $respInterface */
            $respInterface = $response['response'];

            if ($messageId = $respInterface->getHeaderLine('X-Message-Id')) {
                $message->getHeaders()?->addTextHeader('X-MailerSend-Message-Id', $messageId);
            }

            if ($body = $respInterface->getBody()->getContents()) {
                $message->getHeaders()?->addTextHeader('X-MailerSend-Body', $body);
            }

            return new SentMessage($message, $envelope);
        }catch (MailerSendHttpException $exception){
            throw new TransportException($exception->getMessage(), $exception->getCode());
        }
    }

    protected function getFrom(RawMessage $message): array
    {
        $from = $message->getFrom();

        if (count($from) > 0) {
            return ['name' => $from[0]->getName(), 'email' => $from[0]->getAddress()];
        }

        return ['email' => '', 'name' => ''];
    }

    protected function getReplyTo(RawMessage $message): array
    {
        $from = $message->getReplyTo();

        if (count($from) > 0) {
            return ['name' => $from[0]->getName(), 'email' => $from[0]->getAddress()];
        }

        return ['email' => '', 'name' => ''];
    }

    /**
     * @throws \MailerSend\Exceptions\MailerSendAssertException
     */
    protected function getRecipients(string $type, RawMessage $message): array
    {
        $recipients = [];

        if ($addresses = $message->{'get'.ucfirst($type)}()) {
            foreach ($addresses as $address) {
                $recipients[] = new Recipient($address->getAddress(), $address->getName());
            }
        }

        return $recipients;
    }

    protected function getAttachments(RawMessage $message): array
    {
        $attachments = [];

        foreach ($message->getAttachments() as $attachment) {
            /** @var DataPart $attachment */

            if ($attachment->getMediaSubtype() === self::MAILERSEND_DATA_SUBTYPE) {
                continue;
            }

            $attachments[] = new Attachment(
                $attachment->getBody(),
                $attachment->getPreparedHeaders()->get('content-disposition')?->getParameter('filename'),
                $attachment->getPreparedHeaders()->get('content-disposition')?->getBody(),
                $attachment->getPreparedHeaders()->get('content-id')?->getBodyAsString()
            );
        }

        return $attachments;
    }

    /**
     * @param  RawMessage  $message
     * @param  array  $payload
     * @throws \JsonException
     */
    protected function getAdditionalData(RawMessage $message): array
    {
        $defaultValues = [
            'template_id' => null,
            'personalization' => [],
            'tags' => [],
            'precedence_bulk_header' => null,
            'send_at' => null,
        ];

        foreach ($message->getAttachments() as $attachment) {
            /** @var DataPart $attachment */

            if ($attachment->getMediaSubtype() !== self::MAILERSEND_DATA_SUBTYPE) {
                continue;
            }

            return array_merge($defaultValues,
                json_decode($attachment->getBody(), true, 512, JSON_THROW_ON_ERROR));
        }

        return $defaultValues;
    }

    public function __toString(): string
    {
        return 'mailersend';
    }
}
