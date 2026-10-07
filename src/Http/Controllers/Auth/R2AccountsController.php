<?php

namespace R2Cloud\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use R2Cloud\Services\R2AccountsTokenService;

class R2LogoutController extends Controller
{
    public function logout(Request $request, R2AccountsTokenService $tokenService)
    {
        $user = Auth::user();

        if ($user && $user->access_token) {
            // 1. Tell Accounts App to revoke the specific Passport access token
            try {
                $tokenService->revokeToken($user->access_token);
            } catch (\Exception $e) {
                // Log failure but proceed with local logout
                \Log::warning('Failed to revoke remote token during logout.');
            }
        }

        // 2. Log out of DigiSign locally
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // 3. Redirect to Accounts App to destroy the central SSO session
        // Pass a redirect parameter so Accounts knows where to send the user after
        $accountsLogoutUrl = config('r2cloud.accounts.base_url') . '/logout?redirect=' . urlencode(url('/login'));

        return redirect($accountsLogoutUrl);
    }
}
