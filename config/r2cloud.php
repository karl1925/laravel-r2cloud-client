<?php

return [
    'accounts' => [
        'base_url' => env('ACCOUNTS_BASE_URL', 'https://dictr2.cloud'),
        'client_id' => env('ACCOUNTS_CLIENT_ID'),
        'client_secret' => env('ACCOUNTS_CLIENT_SECRET'),
        'scope' => env('ACCOUNTS_SCOPE', 'profile email'),
        'redirect' => env('ACCOUNTS_REDIRECT_URI', 'http://localhost:8000/auth/callback'),
        'authorize_endpoint' => env('ACCOUNTS_AUTHORIZE_ENDPOINT', '/oidc/authorize'),
        'token_endpoint' => env('ACCOUNTS_TOKEN_ENDPOINT', '/oidc/token'),
        'user_endpoint' => env('ACCOUNTS_USER_ENDPOINT', '/oidc/userinfo'),
        'revoke_endpoint' => env('ACCOUNTS_REVOKE_ENDPOINT', '/oauth/tokens'),
    ],
];
