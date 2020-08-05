<?php

return [
    'api_key' => env('MAILERSEND_API_KEY'),
    'host' => env('MAILERSEND_API_HOST', 'api.mailersend.com'),
    'protocol' => env('MAILERSEND_API_PROTO', 'https'),
    'api_path' => env('MAILERSEND_API_PATH', 'v1'),
];