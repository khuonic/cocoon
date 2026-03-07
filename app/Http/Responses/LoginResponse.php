<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $token = $request->user()->createToken('mobile')->plainTextToken;

        session()->flash('api_token', $token);

        $this->flashSyncToken($request->input('email'), $request->input('password'));

        return redirect()->intended(config('fortify.home'));
    }

    private function flashSyncToken(string $email, string $password): void
    {
        $syncApiUrl = config('cocon.sync_api_url');
        if (! $syncApiUrl) {
            return;
        }

        $response = Http::timeout(5)->post("{$syncApiUrl}/api/login", [
            'email' => $email,
            'password' => $password,
        ]);

        if ($response->ok() && $response->json('token')) {
            session()->flash('sync_token', $response->json('token'));
        }
    }
}
