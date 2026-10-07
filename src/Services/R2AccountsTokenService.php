<?php

namespace R2Cloud\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class R2AccountsTokenService
{
    public function getTokensFromCode(string $code): array
    {
        $response = Http::asForm()->post($this->url(config('r2cloud.accounts.token_endpoint', '/oauth/token')), [
            'grant_type' => 'authorization_code',
            'client_id' => config('r2cloud.accounts.client_id'),
            'client_secret' => config('r2cloud.accounts.client_secret'),
            'redirect_uri' => config('r2cloud.accounts.redirect'),
            'code' => $code,
        ]);

        if ($response->failed()) {
            Log::error('R2 Cloud token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('R2 Cloud token exchange failed.');
        }

        return $response->json();
    }

    public function refreshToken(string $refreshToken): array
    {
        $response = Http::asForm()->post($this->url(config('r2cloud.accounts.token_endpoint', '/oauth/token')), [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => config('r2cloud.accounts.client_id'),
            'client_secret' => config('r2cloud.accounts.client_secret'),
            'scope' => config('r2cloud.accounts.scope', 'profile email'),
        ]);

        if ($response->failed()) {
            Log::error('R2 Cloud token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('R2 Cloud token refresh failed.');
        }

        return $response->json();
    }

    public function getUserProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get($this->url(config('r2cloud.accounts.user_endpoint', '/api/user')));

        if ($response->failed()) {
            Log::error('R2 Cloud user profile request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('R2 Cloud user profile request failed.');
        }

        return $response->json();
    }

    public function revokeToken(string $accessToken): void
    {
        $response = Http::withToken($accessToken)
            ->delete($this->url(config('r2cloud.accounts.revoke_endpoint', '/oauth/tokens')));

        if ($response->failed()) {
            Log::warning('R2 Cloud token revocation failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('R2 Cloud token revocation failed.');
        }
    }

    protected function url(string $endpoint): string
    {
        return rtrim(config('r2cloud.accounts.base_url'), '/') . '/' . ltrim($endpoint, '/');
    }
}
