<?php

namespace R2Cloud\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use R2Cloud\Services\R2AccountsTokenService;
use Exception;

class R2EnsureValidAccountsToken
{
    protected R2AccountsTokenService $tokenService;

    public function __construct(R2AccountsTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return redirect('/login');
        }

        // Check if token expires in the next 5 minutes
        if ($user->token_expires_at && $user->token_expires_at->subMinutes(5)->isPast()) {
            try {
                // Attempt to refresh the token
                $tokenData = $this->tokenService->refreshToken($user->refresh_token);

                // Update user with new tokens
                $user->update([
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? $user->refresh_token,
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
                ]);
            } catch (Exception $e) {
                // If refresh fails (e.g., refresh token revoked), log them out locally
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect('/login')->with('error', 'Session expired. Please log in again.');
            }
        }

        return $next($request);
    }
}
