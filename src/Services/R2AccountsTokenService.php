<?php

namespace R2Cloud\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class R2AccountsTokenService
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('r2cloud.accounts.base_url'), '/');
        $this->clientId = config('r2cloud.accounts.client_id');
        $this->clientSecret = config('r2cloud.accounts.client_secret');
        $this->redirectUri = config('r2cloud.accounts.redirect');
    }

    /**
     * Exchange an Authorization Code for Access & Refresh Tokens.
     */
    public function getTokensFromCode(string $code): array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUri,
            'code' => $code,
        ]);

        if (!$response->successful()) {
            Log::error('Accounts App token exchange failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception('Failed to exchange authorization code for tokens.');
        }

        return $response->json();
    }

    /**
     * Refresh an expired Access Token using a Refresh Token.
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth/token", [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => config('r2cloud.accounts.client_id'),
            'redirect_uri' => config('r2cloud.accounts.redirect'),
            'scope' => config('r2cloud.accounts.scope', 'profile email'),
        ]);

        if (!$response->successful()) {
            Log::error('Accounts App token refresh failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception('Failed to refresh access token.');
        }

        return $response->json();
    }

    /**
     * Fetch user profile details from the Accounts App using an Access Token.
     */
    public function getUserProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->get("{$this->baseUrl}/api/user");

        if (!$response->successful()) {
            Log::error('Failed to fetch user profile from Accounts App', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new Exception('Failed to fetch user profile.');
        }

        return $response->json();
    }

    /**
     * Revoke a specific Access Token (used on logout).
     */
    public function revokeToken(string $accessToken): bool
    {
        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->delete("{$this->baseUrl}/oauth/tokens");

        return $response->successful();
    }
}
