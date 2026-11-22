<?php

namespace App\Services\Party;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Le vocal secret d'Antoine : stocké sur le disque privé des photos du memory, jamais exposé publiquement.
 */
class SecretVoice
{
    public const PATH = 'party/secret/vocal.m4a';

    public function disk(): Filesystem
    {
        return Storage::disk(config('party.memory_photos_disk'));
    }

    /**
     * Si le disque est indisponible, on le signale dans les logs et la page l'indique simplement.
     */
    public function exists(): bool
    {
        try {
            return $this->disk()->exists(self::PATH);
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    /**
     * @param  resource|string  $contents
     */
    public function store(mixed $contents): void
    {
        $this->disk()->put(self::PATH, $contents);
    }
}
