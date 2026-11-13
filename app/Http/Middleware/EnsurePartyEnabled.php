<?php

namespace App\Http\Middleware;

use App\Party\PartyEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Le mini-site n'existe que si PARTY_ENABLED=true, et n'est jamais indexé.
 */
class EnsurePartyEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(PartyEvent::isEnabled(), 404);

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
