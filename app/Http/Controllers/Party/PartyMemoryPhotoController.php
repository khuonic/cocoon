<?php

namespace App\Http\Controllers\Party;

use App\Http\Controllers\Controller;
use App\Services\Party\MemoryPhotos;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PartyMemoryPhotoController extends Controller
{
    /**
     * Sert une photo du memory depuis le disque privé, uniquement aux invités (middleware d'accès).
     */
    public function __invoke(int $photo, MemoryPhotos $photos): StreamedResponse
    {
        abort_unless($photos->exists($photo), 404);

        return $photos->disk()->response($photos->path($photo), "memory-{$photo}.jpg", [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
