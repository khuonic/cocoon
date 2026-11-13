<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePartyAccess;
use App\Http\Requests\Party\EnterPartyRequest;
use App\Party\PartyEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PartyAccessController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (PartyEvent::hasValidAccessCookie($request->cookie(PartyEvent::ACCESS_COOKIE))) {
            return to_route('party.home');
        }

        return Inertia::render('Party/Enter');
    }

    public function store(EnterPartyRequest $request): RedirectResponse
    {
        if (! PartyEvent::passwordMatches($request->validated('password'))) {
            throw ValidationException::withMessages(['password' => 'Mauvais mot de passe.']);
        }

        return to_route('party.home')
            ->withCookie(cookie(PartyEvent::ACCESS_COOKIE, PartyEvent::accessToken(), EnsurePartyAccess::COOKIE_MINUTES));
    }
}
