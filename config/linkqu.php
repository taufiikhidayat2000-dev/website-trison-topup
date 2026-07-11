<?php

return [
    'username' => env('LINKQU_USERNAME'),
    'pin' => env('LINKQU_PIN'),
    'client_id' => env('LINKQU_CLIENT_ID'),
    'client_secret' => env('LINKQU_CLIENT_SECRET'),
    'signature_key' => env('LINKQU_SIGNATURE_KEY'),
    'is_production' => env('LINKQU_IS_PRODUCTION', false),
    'callback_url' => env('LINKQU_CALLBACK'),
];
