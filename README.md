# Laravel R2 Cloud Client

DICT R2 Cloud authentication and identity integration for Laravel applications.

## Quick Installation

### 1. Install the package

```bash
composer config repositories.r2cloud-client vcs https://github.com/karl1925/laravel-r2cloud-client.git
composer require karl1925/laravel-r2cloud-client:dev-main
php artisan vendor:publish --tag=config.r2cloud
```

### 2. Publish the configuration

```bash
php artisan vendor:publish --tag=config.r2cloud
```

### 3. Run the migrations

```bash
php artisan migrate
```

### 4. Add R2 Cloud settings to `.env`

```env
ACCOUNTS_BASE_URL=https://dictr2.cloud
ACCOUNTS_CLIENT_ID=
ACCOUNTS_CLIENT_SECRET=
ACCOUNTS_REDIRECT_URI=https://your-domain.com/auth/callback
ACCOUNTS_SCOPE="openid profile email"
```

### 5. Clear the configuration cache

```bash
php artisan optimize:clear
```

### 6. Start the application

```bash
php artisan serve
```

Open:

```text
https://your-domain.com/login
```

---

# Documentation

## Overview

Laravel R2 Cloud Client provides Laravel applications with centralized authentication through **DICT R2 Cloud**.

The package handles:

- R2 Cloud OAuth authentication
- Authorization-code login
- Access-token management
- Refresh-token handling
- Local user synchronization
- R2 Cloud logout
- Token validation middleware
- User-update webhooks
- Laravel session authentication

The consuming Laravel application does not need to implement the OAuth flow itself.

---

## Requirements

- PHP 8.3+
- Laravel 13+
- Composer
- An R2 Cloud OAuth client

The consuming application's existing `App\Models\User` model is used automatically.

---

# Configuration

After installation, publish the package configuration:

```bash
php artisan vendor:publish --tag=config.r2cloud
```

The file will be created at:

```text
config/r2cloud.php
```

Default configuration:

```php
<?php

return [
    'accounts' => [
        'base_url' => env('ACCOUNTS_BASE_URL', 'https://dictr2.cloud'),

        'client_id' => env('ACCOUNTS_CLIENT_ID'),

        'client_secret' => env('ACCOUNTS_CLIENT_SECRET'),

        'redirect' => env(
            'ACCOUNTS_REDIRECT_URI',
            'https://domain.dictr2.cloud/auth/callback'
        ),

        'scope' => env(
            'ACCOUNTS_SCOPE',
            'openid profile email'
        ),

        'user_endpoint' => env(
            'ACCOUNTS_USER_ENDPOINT',
            '/api/user'
        ),

        'token_endpoint' => env(
            'ACCOUNTS_TOKEN_ENDPOINT',
            '/oauth/token'
        ),

        'revoke_endpoint' => env(
            'ACCOUNTS_REVOKE_ENDPOINT',
            '/oauth/tokens'
        ),

        'authorize_endpoint' => env(
            'ACCOUNTS_AUTHORIZE_ENDPOINT',
            '/oauth/authorize'
        ),

        'logout_endpoint' => env(
            'ACCOUNTS_LOGOUT_ENDPOINT',
            '/logout'
        ),

        'validate_state' => env(
            'ACCOUNTS_VALIDATE_STATE',
            false
        ),
    ],

    'routes' => [
        'accounts_login' => env(
            'R2CLOUD_ACCOUNTS_LOGIN_ROUTE',
            'accounts.login'
        ),

        'accounts_callback' => env(
            'R2CLOUD_ACCOUNTS_CALLBACK_ROUTE',
            'accounts.callback'
        ),

        'login' => env(
            'R2CLOUD_LOGIN_ROUTE',
            'login'
        ),

        'logout' => env(
            'R2CLOUD_LOGOUT_ROUTE',
            'logout'
        ),
    ],
];
```

---

# Environment Variables

Add the R2 Cloud OAuth credentials to `.env`:

```env
ACCOUNTS_BASE_URL=https://dictr2.cloud
ACCOUNTS_CLIENT_ID=your-client-id
ACCOUNTS_CLIENT_SECRET=your-client-secret
ACCOUNTS_REDIRECT_URI=https://your-domain.com/auth/callback
ACCOUNTS_SCOPE="openid profile email"
```

Optional endpoints:

```env
ACCOUNTS_USER_ENDPOINT=/api/user
ACCOUNTS_TOKEN_ENDPOINT=/oauth/token
ACCOUNTS_REVOKE_ENDPOINT=/oauth/tokens
ACCOUNTS_AUTHORIZE_ENDPOINT=/oauth/authorize
ACCOUNTS_LOGOUT_ENDPOINT=/logout
```

Optional state validation:

```env
ACCOUNTS_VALIDATE_STATE=false
```

Set it to `true` if the client requires OAuth state validation.

---

# OAuth Client Configuration

An OAuth client must first be created in R2 Cloud.

Configure the client with:

```text
Client ID
Client Secret
Redirect URI
```

The redirect URI must exactly match:

```text
https://your-domain.com/auth/callback
```

and the value configured in:

```env
ACCOUNTS_REDIRECT_URI=https://your-domain.com/auth/callback
```

---

# Authentication Flow

The package provides the following routes automatically.

| Method | URI | Name |
|---|---|---|
| GET | `/login` | `login` |
| GET | `/auth/accounts/redirect` | `accounts.login` |
| GET | `/auth/callback` | `accounts.callback` |
| POST | `/logout` | `logout` |

The normal login flow is:

```text
Client /login
      ↓
R2 Cloud authorization
      ↓
R2 Cloud login
      ↓
Authorization code
      ↓
Client /auth/callback
      ↓
Access token
      ↓
User profile
      ↓
Local User
      ↓
Laravel session
      ↓
Dashboard
```

---

# Login

The package provides the `/login` route.

If the user is already authenticated:

```text
/login
   ↓
/dashboard
```

Otherwise:

```text
/login
   ↓
R2 Cloud login
```

Applications therefore do not need to create their own login controller.

---

# User Synchronization

After successful authentication, the package retrieves the user's profile from:

```text
/api/user
```

The configured Laravel user model is obtained from:

```php
config('auth.providers.users.model')
```

Normally this is:

```php
App\Models\User
```

The package does not provide its own User model.

The local user is identified by:

```text
accounts_user_id
```

The following information can be synchronized:

```text
name
email
avatar
access_token
refresh_token
token_expires_at
email_verified_at
```

---

# Database

The package automatically loads its migration.

Run:

```bash
php artisan migrate
```

The migration adds the required R2 Cloud fields to the application's existing `users` table.

The password field can be nullable so applications using R2 Cloud exclusively do not require a local password.

Example:

```php
$table->string('password')->nullable()->change();
```

---

# Token Management

The package stores:

```text
access_token
refresh_token
token_expires_at
```

Access tokens are automatically refreshed when they are close to expiration.

The package uses the R2 Cloud OAuth token endpoint:

```text
/oauth/token
```

---

# Token Middleware

The package provides:

```text
r2cloud.token
```

and:

```text
r2.accounts.token
```

Both aliases use:

```php
R2Cloud\Http\Middleware\R2EnsureValidAccountsToken
```

Example:

```php
Route::middleware(['auth', 'r2cloud.token'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    });
});
```

The middleware:

1. Checks whether the user is authenticated.
2. Checks the access-token expiration.
3. Refreshes the token when necessary.
4. Updates the local user.
5. Logs the user out if the refresh fails.

---

# Logout

The package provides:

```text
POST /logout
```

Logout performs:

1. R2 Cloud token revocation.
2. Local Laravel logout.
3. Session invalidation.
4. CSRF-token regeneration.
5. Redirect to R2 Cloud logout.

The user is then returned to the application's login page.

---

# User Update Webhook

The package provides:

```text
POST /api/webhooks/user-updated
```

R2 Cloud can notify connected applications when user information changes.

The webhook verifies:

```text
X-Webhook-Signature
```

using the OAuth client secret.

The signature is calculated using HMAC SHA-256 against the raw request body.

After successful verification, the package updates the local user.

---

# Package Structure

```text
laravel-r2cloud-client/
├── config/
│   └── r2cloud.php
├── database/
│   └── migrations/
├── routes/
│   ├── web.php
│   └── api.php
├── src/
│   ├── R2CloudServiceProvider.php
│   ├── Services/
│   │   └── R2AccountsTokenService.php
│   └── Http/
│       ├── Controllers/
│       │   ├── Auth/
│       │   │   ├── R2AccountsController.php
│       │   │   └── R2LogoutController.php
│       │   └── R2WebhookController.php
│       └── Middleware/
│           └── R2EnsureValidAccountsToken.php
├── composer.json
└── README.md
```

---

# Namespaces

The package uses the `R2Cloud` namespace.

Examples:

```php
R2Cloud\R2CloudServiceProvider
```

```php
R2Cloud\Services\R2AccountsTokenService
```

```php
R2Cloud\Http\Controllers\Auth\R2AccountsController
```

```php
R2Cloud\Http\Controllers\Auth\R2LogoutController
```

```php
R2Cloud\Http\Controllers\R2WebhookController
```

```php
R2Cloud\Http\Middleware\R2EnsureValidAccountsToken
```

The package does not depend on the consuming application's `App\` namespace.

---

# Automatic Laravel Integration

Laravel automatically discovers:

```php
R2Cloud\R2CloudServiceProvider
```

The service provider registers:

- Package configuration
- Package migrations
- Package web routes
- Package API routes
- Token middleware
- Token service

No manual provider registration is normally required.

---

# Package Routes

The package automatically loads its web routes:

```text
GET  /auth/accounts/redirect
GET  /auth/callback
GET  /login
POST /logout
```

Do not duplicate these routes in the consuming application's `routes/web.php`.

---

# Package API Routes

The package automatically loads:

```text
POST /api/webhooks/user-updated
```

Do not duplicate this route in the consuming application's `routes/api.php`.

---

# Updating the Package

To update the package to the latest development version:

```bash
composer update karl1925/laravel-r2cloud-client -W
```

Then:

```bash
php artisan optimize:clear
```

If new package migrations are available:

```bash
php artisan migrate
```

---

# Development Repository

GitHub:

`https://github.com/karl1925/laravel-r2cloud-client`

Package:

```text
karl1925/laravel-r2cloud-client
```

Install:

```bash
composer require karl1925/laravel-r2cloud-client:dev-main
```

---

# License

Proprietary — DICT Region II / R2 Cloud.
