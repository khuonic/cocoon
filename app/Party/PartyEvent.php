<?php

namespace App\Party;

use Carbon\CarbonImmutable;

/**
 * État global de la fête (ouverture, fermeture, accès).
 */
class PartyEvent
{
    public const ACCESS_COOKIE = 'party_access';

    public const PLAYER_COOKIE = 'party_player';

    public static function isEnabled(): bool
    {
        return (bool) config('party.enabled');
    }

    public static function isClosed(): bool
    {
        $closesAt = config('party.closes_at');

        return $closesAt !== null && now()->greaterThan(CarbonImmutable::parse($closesAt, config('app.timezone')));
    }

    /**
     * Valeur du cookie d'accès : dérivée des secrets pour qu'un changement de clé ou de mot de passe révoque les accès.
     */
    public static function accessToken(): ?string
    {
        $key = (string) config('party.access_key');
        $password = (string) config('party.password');

        if ($key === '' && $password === '') {
            return null;
        }

        return hash('sha256', 'party-access|'.$key.'|'.$password);
    }

    public static function hasValidAccessCookie(?string $cookieValue): bool
    {
        $token = static::accessToken();

        return $token !== null && filled($cookieValue) && hash_equals($token, (string) $cookieValue);
    }

    public static function keyMatches(?string $candidate): bool
    {
        $key = config('party.access_key');

        return filled($key) && filled($candidate) && hash_equals((string) $key, (string) $candidate);
    }

    public static function passwordMatches(?string $candidate): bool
    {
        $password = config('party.password');

        return filled($password) && filled($candidate) && hash_equals((string) $password, (string) $candidate);
    }
}
