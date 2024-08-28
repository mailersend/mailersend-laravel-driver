<a href="https://www.mailersend.com"><img src="https://www.mailersend.com/images/logo.svg" width="200px"/></a>

MailerSend Laravel Driver

[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE.md)

# Table of Contents

* [Installation](#installation)
* [Usage](#usage)
* [Support and Feedback](#support-and-feedback)
* [License](#license)

<a name="installation"></a>
# Installation

## Requirements

- Laravel 9.0+
- PHP 8.0+
- Guzzle 7.0+
- An API Key from [mailersend.com](https://www.mailersend.com)

**For Laravel 7.x - 8.x support see [1.x branch](https://github.com/mailersend/mailersend-laravel-driver/tree/1.x)**

## Setup

You can install the package via composer:

```bash
composer require mailersend/laravel-driver
```

After that, you need to set `MAILERSEND_API_KEY` in your `.env` file:

```dotenv
MAILERSEND_API_KEY=
```

Add MailerSend as a Laravel Mailer in `config/mail.php` in `mailers` array:

```php
'mailersend' => [
    'transport' => 'mailersend',
],
```

And set environment variable `MAIL_MAILER` in your `.env` file

```dotenv
MAIL_MAILER=mailersend
```

Also, double check that your `FROM` data is filled in `.env`:

```dotenv
MAIL_FROM_ADDRESS=app@yourdomain.com
MAIL_FROM_NAME="App Name"
```

<a name="usage"></a>
# Usage

### Old Syntax:
This is an example using the build [mailable](https://laravel.com/docs/8.x/mail#writing-mailables) that you can use to send an email with.

`app/Mail/TestEmail.php`

```php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use MailerSend\Helpers\Builder\Variable;
use MailerSend\Helpers\Builder\Personalization;
use MailerSend\LaravelDriver\MailerSendTrait;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels, MailerSendTrait;

    public function build()
    {
        // Recipient for use with variables and/or personalization
        $to = Arr::get($this->to, '0.address');

        return $this
            ->view('emails.test_html')
            ->text('emails.test_text')
            ->attachFromStorageDisk('public', 'example.png')
            // Additional options for MailerSend API features
            ->mailersend(
                template_id: null,
                tags: ['tag'],
                personalization: [
                    new Personalization($to, [
                        'var' => 'variable',
                        'number' => 123,
                        'object' => [
                            'key' => 'object-value'
                        ],
                        'objectCollection' => [
                            [
                                'name' => 'John'
                            ],
                            [
                                'name' => 'Patrick'
                            ]
                        ],
                    ])
                ],
                precedenceBulkHeader: true,
                sendAt: new Carbon('2022-01-28 11:53:20'),
            );
    }
}
```

### New Syntax:
This is an example using the new [mailable](https://laravel.com/docs/9.x/mail#writing-mailables) syntax that you can use to send an email with.

`app/Mail/TestEmail.php`

```php
<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use MailerSend\Helpers\Builder\Personalization;
use MailerSend\Helpers\Builder\Variable;
use MailerSend\LaravelDriver\MailerSendTrait;

class TestEmail extends Mailable
{
    use Queueable, SerializesModels, MailerSendTrait;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test Email',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $to = Arr::get($this->to, '0.address');

        // Additional options for MailerSend API features
        $this->mailersend(
            template_id: null,
            tags: ['tag'],
            personalization: [
                new Personalization($to, [
                    'var' => 'variable',
                    'number' => 123,
                    'object' => [
                        'key' => 'object-value'
                    ],
                    'objectCollection' => [
                        [
                            'name' => 'John'
                        ],
                        [
                            'name' => 'Patrick'
                        ]
                    ],
                ])
            ],
            precedenceBulkHeader: true,
            sendAt: new Carbon('2022-01-28 11:53:20'),
        );

        return new Content(
            view: 'emails.test_html',
            text: 'emails.test_text'
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk('public', 'example.png')
        ];
    }
}
```

We provide a `MailerSendTrait` trait that adds a `mailersend` method to the mailable and allows you to use additional options that are available through our API.

After creating the mailable, you can send it using:

```php
use App\Mail\TestEmail;
use Illuminate\Support\Facades\Mail;

Mail::to('recipient@domain.com')
    ->cc('cc@domain.com')
    ->bcc('bcc@domain.com')
    ->send(new TestEmail());
```

Please refer to [Laravel Mail documenation](https://laravel.com/docs/9.x/mail) and [MailerSend API documentation](https://developers.mailersend.com) for more information.

<a name="support-and-feedback"></a>
# Support and Feedback

In case you find any bugs, submit an issue directly here in GitHub.

If you have any troubles using our driver, feel free to contact our support by email [info@mailersend.com](mailto:info@mailersend.com)

Official API documentation is at [https://developers.mailersend.com](https://developers.mailersend.com)

<a name="license"></a>
# License

[The MIT License (MIT)](LICENSE.md)
