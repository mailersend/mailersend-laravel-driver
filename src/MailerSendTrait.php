<?php

namespace MailerSend\LaravelDriver;

use Illuminate\Mail\Mailable;
use Illuminate\Support\Arr;
use Swift_Message;

trait MailerSendTrait
{
    public function mailersend(string $template_id = null, array $variables = [], array $tags = [], array $personalization = [])
    {
        if ($this instanceof Mailable && $this->driver() === 'mailersend') {
            $this->withSwiftMessage(function (Swift_Message $message) use ($tags, $variables, $template_id, $personalization) {
                $mailersendData = [];

                Arr::set($mailersendData, MailerSendTransport::MAILERSEND_DATA_TEMPLATE_ID, $template_id);
                Arr::set($mailersendData, MailerSendTransport::MAILERSEND_DATA_VARIABLES, $variables);
                Arr::set($mailersendData, MailerSendTransport::MAILERSEND_DATA_TAGS, $tags);
                Arr::set($mailersendData, MailerSendTransport::MAILERSEND_DATA_PERSONALIZATION, $personalization);

                $message->addPart(json_encode($mailersendData, JSON_THROW_ON_ERROR),
                    MailerSendTransport::MAILERSEND_DATA);
            });
        }

        return $this;
    }

    protected function driver(): string
    {
        return function_exists('config') ? config('mail.default') : env('MAIL_MAILER');
    }
}
