<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePartyPlayer;
use App\Http\Requests\Party\StorePlayerRequest;
use App\Models\Party\Player;
use App\Party\PartyEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PartyPlayerController extends Controller
{
    public const COOKIE_MINUTES = 60 * 24 * 365;

    public function edit(Request $request): Response
    {
        return Inertia::render('Party/Pseudo', [
            'pseudo' => EnsurePartyPlayer::resolve($request)?->pseudo,
        ]);
    }

    public function store(StorePlayerRequest $request): RedirectResponse
    {
        $player = EnsurePartyPlayer::resolve($request);

        if ($player !== null) {
            $player->update(['pseudo' => $request->validated('pseudo')]);
        } else {
            $player = Player::create([
                'uuid' => (string) Str::uuid(),
                'pseudo' => $request->validated('pseudo'),
            ]);
        }

        return to_route('party.home')
            ->withCookie(cookie(PartyEvent::PLAYER_COOKIE, $player->uuid, self::COOKIE_MINUTES));
    }
}
