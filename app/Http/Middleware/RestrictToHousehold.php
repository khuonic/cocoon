<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToHousehold
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        $allowedEmails = array_column(config('cocon.allowed_users', []), 'email');

        if (! $user || ! in_array($user->email, $allowedEmails, true)) {
            abort(403, 'Accès non autorisé.');
        }

        return $next($request);
    }
}
