<?php

namespace App\Console\Commands;

use App\Services\Party\SecretVoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Envoie le vocal secret d'Antoine (.m4a) sur le disque privé (remplace le précédent).
 */
class PartySecretVoice extends Command
{
    /**
     * @var string
     */
    protected $signature = 'party:secret-voice
        {file : Chemin local du vocal (.m4a ou .mp4)}
        {--disk= : Disque cible (par défaut config party.memory_photos_disk, ex: s3)}';

    /**
     * @var string
     */
    protected $description = 'Envoie le vocal secret de la fête sur le disque privé';

    public function handle(SecretVoice $voice): int
    {
        $file = (string) $this->argument('file');

        if (filled($this->option('disk'))) {
            config(['party.memory_photos_disk' => $this->option('disk')]);
        }

        if (! File::isFile($file)) {
            $this->error("Fichier introuvable : {$file}");

            return self::FAILURE;
        }

        // .m4a et .mp4 partagent le même conteneur MP4 : stockés tels quels, servis en audio/mp4.
        if (! in_array(strtolower(File::extension($file)), ['m4a', 'mp4'], true)) {
            $this->error('Le vocal doit être un fichier .m4a ou .mp4.');

            return self::FAILURE;
        }

        $stream = fopen($file, 'rb');
        $voice->store($stream);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->info('Vocal envoyé sur le disque « '.config('party.memory_photos_disk').' ».');

        return self::SUCCESS;
    }
}
