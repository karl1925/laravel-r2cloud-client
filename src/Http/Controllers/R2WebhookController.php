<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class R2WebhookController extends Controller
{
    public function handleUserUpdate(Request $request)
    {
        // 1. Verify Signature to ensure the request actually came from Accounts App
        $expectedSignature = hash_hmac('sha256', $request->getContent(), config('r2cloud.accounts.client_secret'));

        abort_if(!hash_equals($expectedSignature, $request->header('X-Webhook-Signature')), 401);

        // 2. Update local user data silently
        User::where('accounts_user_id', $request->id)->update([
            'name' => $request->name,
            'email' => $request->email,
            'avatar' => $request->avatar,
        ]);

        return response()->json(['status' => 'synchronized']);
    }
}
