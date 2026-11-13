<?php

namespace App\Http\Middleware;

use App\Models\Party\Player;
use App\Party\PartyEvent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Résout le joueur depuis son cookie, ou l'envoie choisir un pseudo.
 */
class EnsurePartyPlayer
{
    public function handle(Request $request, Closure $next): Response
    {
        $player = static::resolve($request);

        if ($player === null) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Choisis un pseudo pour jouer.'], 403)
                : to_route('party.player.edit');
        }

        $request->attributes->set(Player::class, $player);

        return $next($request);
    }

    public static function resolve(Request $request): ?Player
    {
        $uuid = $request->cookie(PartyEvent::PLAYER_COOKIE);

        if (! is_string($uuid) || $uuid === '') {
            return null;
        }

        return Player::query()->where('uuid', $uuid)->first();
    }
}
