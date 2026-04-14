<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Native\Mobile\Facades\SecureStorage;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $token = $request->user()->createToken('mobile')->plainTextToken;

        SecureStorage::set('cocoon_auth_token', $token);
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

        try {
            $response = Http::timeout(30)->acceptJson()->post("{$syncApiUrl}/api/login", [
                'email' => $email,
                'password' => $password,
                'device_name' => 'cocoon-mobile',
            ]);

            if ($response->ok() && $response->json('token')) {
                session()->flash('sync_token', $response->json('token'));
            }
        } catch (\Exception $e) {
            // Sync token non critique — la sync s'activera au prochain login
        }
    }
}
