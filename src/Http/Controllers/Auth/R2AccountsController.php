<?php

namespace R2Cloud\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use R2Cloud\Services\R2AccountsTokenService;

class R2AccountsController extends Controller
{
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('r2cloud.accounts.client_id'),
            'redirect_uri' => config('r2cloud.accounts.redirect'),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
        ]);

        return redirect(config('r2cloud.accounts.base_url') . '/oauth/authorize?' . $query);
    }

    public function callback(Request $request, R2AccountsTokenService $tokenService)
    {
        $state = $request->session()->pull('oauth_state');

        // abort_unless($state && $state === $request->state, 403, 'Invalid OAuth state');

        // try {
        // 1. Exchange OAuth code for tokens from Accounts App
        $tokenData = $tokenService->getTokensFromCode($request->code);

        // 2. Fetch the user profile (which now includes ['roles' => ['admin', 'signer']])
        $accountUser = $tokenService->getUserProfile($tokenData['access_token']);

        // 3. Find or create the user in DigiSign local DB
        $user = User::updateOrCreate(
            ['accounts_user_id' => $accountUser['id']],
            [
                'name' => $accountUser['name'],
                'email' => $accountUser['email'],
                'avatar' => $accountUser['avatar'] ?? null,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]
        );

        // 4. Sync roles locally (via Spatie Permission package)
        if (isset($accountUser['roles'])) {
            $user->syncRoles($accountUser['roles']);
        }

        // 5. Authenticate the user into DigiSign
        Auth::login($user, true);

        return redirect('/dashboard');
        // } catch (\Exception $e) {
        // return redirect('/login')->with('error', $e->getMessage());
        // }
    }

    public function getValidToken(User $user, R2AccountsTokenService $tokenService): string
    {
        // Check if token expires within 5 minutes
        if ($user->token_expires_at && $user->token_expires_at->subMinutes(5)->isPast()) {
            $tokenData = $tokenService->refreshToken($user->refresh_token);

            $user->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $user->refresh_token,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in']),
            ]);
        }

        return $user->access_token;
    }

    public function destroy(Request $request, R2AccountsTokenService $tokenService)
    {
        $user = Auth::user();

        // 1. Revoke the Access Token on Accounts IDP if stored on the user
        if ($user && $user->access_token) {
            try {
                $tokenService->revokeToken($user->access_token);
            } catch (\Exception $e) {
                // Log and continue local logout even if remote revocation fails
                logger()->warning('Failed to revoke Accounts token on logout: ' . $e->getMessage());
            }
        }

        // 2. Clear DigiSign local session
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 3. Redirect to Accounts to destroy central session
        $accountsLogoutUrl = 'https://dictr2.cloud/oauth/logout?' . http_build_query([
            'redirect' => url('/login'),
        ]);

        return redirect()->away($accountsLogoutUrl);
    }
}
