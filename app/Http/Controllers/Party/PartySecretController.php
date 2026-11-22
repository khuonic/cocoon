<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Services\Party\SecretVoice;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Le vocal d'Antoine, utilisé pour un jeu papier : accessible à tout invité (clé du QR code ou mot de passe).
 */
class PartySecretController extends Controller
{
    public function show(SecretVoice $voice): Response
    {
        return Inertia::render('Party/Secret', [
            'available' => $voice->exists(),
        ]);
    }

    public function voice(SecretVoice $voice): StreamedResponse
    {
        abort_unless($voice->exists(), 404);

        return $voice->disk()->response(SecretVoice::PATH, 'antoine.m4a', [
            'Content-Type' => 'audio/mp4',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
