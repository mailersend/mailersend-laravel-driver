<a href="https://www.mailersend.com"><img src="https://www.mailersend.com/images/logo.svg" width="200px"/></a>

MailerSend Laravel Driver

[![MIT licensed](https://img.shields.io/badge/license-MIT-blue.svg)](./LICENSE.md)

# Table of Contents

* [Installation](#installation)
* [Upgrade and Guzzle 6 support](#upgrade)
* [Usage](#usage)
* [Support and Feedback](#support-and-feedback)
* [License](#license)

<a name="installation"></a>
# Installation

## Requirements

- Laravel 7.0+
- PHP 7.4+
- Guzzle 7.0+
- An API Key from [mailersend.com](https://www.mailersend.com)

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

<a name="upgrade"></a>
# Upgrade and Guzzle 6 support

## Upgrading from v0.1

If you are upgrading from `v0.1` branches, please do note that you will need to upgrade Guzzle to atleast version 7. [Please consult official guide for more info](https://github.com/guzzle/guzzle/blob/master/UPGRADING.md).

<a name="usage"></a>
# Usage

This is an example [mailable](https://laravel.com/docs/7.x/mail#writing-mailables) that you can use to send an email with.

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
        $to = Arr::get($this->to, '0.address');

        return $this->view('emails.test_html')
            ->text('emails.test_text')
            ->attachFromStorageDisk('public', 'example.png')
            ->mailersend(
                // Template ID
                null,
                // Variables for simple personalization
                [
                    new Variable($to, ['name' => 'Your Name'])
                ],
                // Tags
                ['tag'],
                // Advanced personalization
                [
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
                ]
            );
    }
}
```

Attachments are added through standard Laravel methods.

We provide a `MailerSendTrait` trait that adds a `mailersend` method to the mailable and allows you to use templates, variables & tags support available through our API.

After creating the mailable, you can send it using:

```php
use App\Mail\TestEmail;
use Illuminate\Support\Facades\Mail;

Mail::to('recipient@domain.com')->send(new TestEmail());
```

Please refer to [Laravel Mail documenation](https://laravel.com/docs/7.x/mail) and [MailerSend API documentation](https://developers.mailersend.com) for more information.

<a name="support-and-feedback"></a>
# Support and Feedback

In case you find any bugs, submit an issue directly here in GitHub.

If you have any troubles using our driver, feel free to contact our support by email [info@mailersend.com](mailto:info@mailersend.com)

Official API documentation is at [https://developers.mailersend.com](https://developers.mailersend.com)

<a name="license"></a>
# License

[The MIT License (MIT)](LICENSE.md)
