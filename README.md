<a href="https://www.mailersend.com"><img src="https://www.mailersend.com/site/themes/new/images/logo.svg" width="200px"/></a>

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

- Laravel 7.0+
- PHP 7.4+
- An API Key from [mailersend.com](https://www.mailersend.com)

## Setup

You can install the package via composer:

```bash
composer require mailersend/laravel-driver
```

After that, you need to set `MAILERSEND_API_KEY` in your `.env` file:

```
MAILERSEND_API_KEY=
```

And enable MailerSend as a Laravel Mail Driver in `config/mail.php`

```php
'driver' => env('MAIL_DRIVER', 'mailersend'),
```

Or by setting the environment variable `MAIL_DRIVER` in your `.env` file

```php
MAIL_DRIVER=mailersend
```

<a name="usage"></a>
# Usage

_TBD_

<a name="support-and-feedback"></a>
# Support and Feedback

In case you find any bugs, submit an issue directly here in GitHub.

If you have any troubles using our driver, feel free to contact our support by email [info@mailersend.com](mailto:info@mailersend.com)

Official API documentation is at [https://developers.mailersend.com](https://developers.mailersend.com)

<a name="license"></a>
# License

[The MIT License (MIT)](LICENSE.md)