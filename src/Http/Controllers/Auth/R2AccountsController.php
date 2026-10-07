<?php

namespace R2Cloud\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
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
            'scope' => config('r2cloud.accounts.scope', 'profile email'),
            'state' => $state,
        ]);

        return redirect(config('r2cloud.accounts.base_url') . '/oauth/authorize?' . $query);
    }

    public function callback(Request $request, R2AccountsTokenService $tokenService)
    {
        if ($request->filled('error')) {
            abort(400, $request->input('error_description', $request->input('error')));
        }

        $state = $request->session()->pull('oauth_state');

        if (config('r2cloud.accounts.validate_state', false)) {
            abort_unless($state && hash_equals($state, (string) $request->state), 403, 'Invalid OAuth state.');
        }

        abort_unless($request->filled('code'), 400, 'Authorization code is missing.');

        $tokenData = $tokenService->getTokensFromCode($request->code);
        $accountUser = $tokenService->getUserProfile($tokenData['access_token']);

        logger()->info('R2 Cloud UserInfo returned', ['userinfo' => $accountUser]);

        $userModel = config('auth.providers.users.model');

        $user = $userModel::updateOrCreate(
            ['email' => $accountUser['email']],
            [
                'accounts_user_id' => $accountUser['id'],
                'name' => $accountUser['name'],
                'avatar' => $accountUser['avatar'] ?? null,
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
            ]
        );

        if (isset($accountUser['roles']) && method_exists($user, 'syncOidcRoles')) {
            $user->syncOidcRoles($accountUser['roles']);
        }

        Auth::login($user, true);

        return redirect('/dashboard');
    }

    public function getValidToken($user, R2AccountsTokenService $tokenService): string
    {
        if ($user->token_expires_at && $user->token_expires_at->copy()->subMinutes(5)->isPast()) {
            $tokenData = $tokenService->refreshToken($user->refresh_token);

            $user->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $user->refresh_token,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
            ]);
        }

        return $user->access_token;
    }

    public function logout(Request $request, R2AccountsTokenService $tokenService)
    {
        $user = Auth::user();

        if ($user && $user->access_token) {
            try {
                $tokenService->revokeToken($user->access_token);
            } catch (\Throwable $e) {
                logger()->warning('Failed to revoke Accounts token on logout: ' . $e->getMessage());
            }
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $accountsLogoutUrl = rtrim(config('r2cloud.accounts.base_url'), '/') .
            config('r2cloud.accounts.logout_endpoint', '/logout') . '?' .
            http_build_query(['redirect' => url('/login')]);

        return redirect()->away($accountsLogoutUrl);
    }
}
