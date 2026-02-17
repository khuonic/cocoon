<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        /** @var Request $request */
        $token = $request->user()->createToken('mobile')->plainTextToken;

        session()->flash('api_token', $token);

        return redirect()->intended(config('fortify.home'));
    }
}
