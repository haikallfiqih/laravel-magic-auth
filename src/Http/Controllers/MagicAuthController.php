<?php

namespace LaravelLinkAuth\MagicAuth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LaravelLinkAuth\MagicAuth\MagicAuth;

class MagicAuthController extends Controller
{
    protected $magicAuth;

    public function __construct(MagicAuth $magicAuth)
    {
        $this->magicAuth = $magicAuth;
    }

    public function sendMagicLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'guard' => 'required|string|in:' . implode(',', array_keys(config('magic-auth.guards'))),
        ]);

        $this->magicAuth->sendMagicLink($request->email, $request->guard);

        return response()->json([
            'message' => 'Magic link has been sent to your email address.',
        ]);
    }

    public function verify(Request $request)
    {
        if (!$request->hasValidSignature()) {
            abort(401);
        }

        $redirectTo = $this->magicAuth->verifyAndLogin($request->token, $request->guard);

        if (!$redirectTo) {
            return redirect()->route('login')
                ->withErrors(['error' => 'Invalid or expired magic link.']);
        }

        return redirect($redirectTo);
    }
}
