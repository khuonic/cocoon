<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;
use Native\Mobile\Facades\SecureStorage;

class BiometricController extends Controller
{
    public function available(): JsonResponse
    {
        $token = SecureStorage::get('cocoon_auth_token');

        if (! $token) {
            return response()->json(['available' => false]);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        return response()->json([
            'available' => $accessToken && $accessToken->tokenable,
        ]);
    }

    public function show(): Response
    {
        return Inertia::render('auth/BiometricLogin');
    }

    public function verify(Request $request): RedirectResponse
    {
        $token = SecureStorage::get('cocoon_auth_token');

        if (! $token) {
            return back()->withErrors([
                'biometric' => 'Aucune session sauvegardée. Connectez-vous avec votre mot de passe.',
            ]);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken || ! $accessToken->tokenable) {
            return back()->withErrors([
                'biometric' => 'Session expirée. Connectez-vous avec votre mot de passe.',
            ]);
        }

        Auth::login($accessToken->tokenable);

        $request->session()->regenerate();

        session()->flash('api_token', $token);

        return redirect()->intended(config('fortify.home'));
    }
}
