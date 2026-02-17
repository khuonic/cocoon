<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PublishRelease extends Command
{
    protected $signature = 'app:publish-release {apk_path : Chemin vers le fichier APK} {--changelog= : Description des changements}';

    protected $description = 'Publie une nouvelle version de l\'APK sur le serveur';

    public function handle(): int
    {
        $apkPath = $this->argument('apk_path');

        if (! file_exists($apkPath)) {
            $this->error("Le fichier APK source n'existe pas : {$apkPath}");

            return self::FAILURE;
        }

        $version = config('nativephp.version');
        $versionCode = (int) config('nativephp.version_code');
        $changelog = $this->option('changelog') ?? '';
        $filename = "cocoon-{$version}.apk";
        $destination = "releases/{$filename}";

        Storage::makeDirectory('releases');
        Storage::put($destination, file_get_contents($apkPath));

        $release = [
            'version' => $version,
            'version_code' => $versionCode,
            'changelog' => $changelog,
            'filename' => $filename,
            'released_at' => now()->toIso8601String(),
        ];

        Storage::put('releases/latest.json', json_encode($release, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $size = round(filesize($apkPath) / 1024 / 1024, 2);

        $this->info('Release publiée avec succès !');
        $this->table(
            ['Champ', 'Valeur'],
            [
                ['Version', $version],
                ['Version code', $versionCode],
                ['Taille', "{$size} Mo"],
                ['Fichier', Storage::path($destination)],
                ['Changelog', $changelog ?: '(aucun)'],
            ],
        );

        return self::SUCCESS;
    }
}
