<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class BiometricController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('auth/BiometricLogin');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $accessToken = PersonalAccessToken::findToken($request->input('token'));

        if (! $accessToken || ! $accessToken->tokenable) {
            return back()->withErrors([
                'token' => 'Token invalide ou expiré.',
            ]);
        }

        Auth::login($accessToken->tokenable);

        $request->session()->regenerate();

        return redirect()->intended(config('fortify.home'));
    }
}
