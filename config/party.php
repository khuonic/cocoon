<?php

use App\Party\Games\VoieDeLaSagesse;

return [

    /*
    |--------------------------------------------------------------------------
    | Mini-site de la fête
    |--------------------------------------------------------------------------
    |
    | Mini-site invités servi sous /fete. Désactivé par défaut : il ne doit être
    | activé que sur le serveur Cloud (PARTY_ENABLED=true), jamais dans l'APK.
    |
    */

    'enabled' => (bool) env('PARTY_ENABLED', false),

    'title' => 'Antoine a 35 ans',

    /*
    | Clé contenue dans l'URL du QR code (?k=...) et mot de passe de secours.
    */

    'access_key' => env('PARTY_ACCESS_KEY'),

    'password' => env('PARTY_PASSWORD'),

    /*
    | Après cette date, les jeux passent en lecture seule (classement visible).
    */

    'closes_at' => env('PARTY_CLOSES_AT', '2027-01-30 23:59:59'),

    /*
    | Jeux disponibles, dans l'ordre d'affichage du hub.
    */

    'games' => [
        VoieDeLaSagesse::class,
    ],

];
