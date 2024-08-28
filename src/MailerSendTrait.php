<?php

namespace MailerSend\LaravelDriver;

use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Arr;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

trait MailerSendTrait
{
    public function mailersend(
        string $template_id = null,
        array $tags = [],
        array $personalization = [],
        ?bool $precedenceBulkHeader = null,
        Carbon $sendAt = null
    ): static {
        if ($this instanceof Mailable && $this->driver() === 'mailersend') {
            $this->withSymfonyMessage(function (Email $message) use (
                $tags,
                $template_id,
                $personalization,
                $sendAt,
                $precedenceBulkHeader
            ) {
                $mailersendData = [];

                Arr::set($mailersendData, MailerSendTransport::MAILERSEND_DATA_TEMPLATE_ID, $template_id);
                Arr::set($mailersendData, MailerSendTransport::MAILERSEND_DATA_TAGS, $tags);
                Arr::set($mailersendData, MailerSendTransport::MAILERSEND_DATA_PERSONALIZATION, $personalization);
                Arr::set($mailersendData, MailerSendTransport::MAILERSEND_DATA_PRECENDECE_BULK_HEADER, $precedenceBulkHeader);
                Arr::set($mailersendData, MailerSendTransport::MAILERSEND_DATA_SEND_AT, $sendAt?->timestamp);

                $message->addPart(new DataPart(
                    json_encode($mailersendData, JSON_THROW_ON_ERROR),
                    MailerSendTransport::MAILERSEND_DATA_SUBTYPE.'.json',
                    MailerSendTransport::MAILERSEND_DATA_TYPE.'/'.MailerSendTransport::MAILERSEND_DATA_SUBTYPE
                ));
            });

            if ($template_id !== null) {
                $this->html('');
            }
        }

        return $this;
    }

    protected function driver(): string
    {
        return function_exists('config') ? config('mail.default') : env('MAIL_MAILER');
    }
}
