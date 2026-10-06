<?php

return [

    'accounts' => [
        'base_url' => env('ACCOUNTS_BASE_URL', 'https://dictr2.cloud'),
        'client_id' => env('ACCOUNTS_CLIENT_ID'),
        'client_secret' => env('ACCOUNTS_CLIENT_SECRET'),
        'redirect' => env('ACCOUNTS_REDIRECT_URI', 'https://yourdomain.dictr2.cloud/auth/callback'),
    ],
];