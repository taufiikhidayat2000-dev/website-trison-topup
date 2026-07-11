<?php

return [
    // Off by default until a reseller account + API secret key are issued.
    'enabled' => env('LAPAKGAMING_ENABLED', false),
    'secret_key' => env('LAPAKGAMING_SECRET_KEY'),
    'is_production' => env('LAPAKGAMING_IS_PRODUCTION', false),
    'callback_url' => env('LAPAKGAMING_CALLBACK'),
];
