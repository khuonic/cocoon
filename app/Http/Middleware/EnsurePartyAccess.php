<?php

namespace App\Http\Middleware;

use App\Party\PartyEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Accès réservé aux invités : clé du QR code (?k=) ou cookie posé après le mot de passe.
 */
class EnsurePartyAccess
{
    public const COOKIE_MINUTES = 60 * 24 * 30;

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->query->has('k')) {
            if (PartyEvent::keyMatches($request->query('k'))) {
                return redirect($request->fullUrlWithoutQuery('k'))
                    ->withCookie(cookie(PartyEvent::ACCESS_COOKIE, PartyEvent::accessToken(), self::COOKIE_MINUTES));
            }

            return to_route('party.enter');
        }

        if (! PartyEvent::hasValidAccessCookie($request->cookie(PartyEvent::ACCESS_COOKIE))) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Accès réservé aux invités.'], 403)
                : to_route('party.enter');
        }

        return $next($request);
    }
}
