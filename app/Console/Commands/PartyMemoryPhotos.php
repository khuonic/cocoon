<?php

namespace App\Console\Commands;

use App\Services\Party\MemoryPhotos;
use GdImage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Importe les photos du memory : recadrage portrait 3:4 (format des cartes), redimensionnement, ré-encodage JPEG
 * (ce qui supprime toutes les métadonnées EXIF, dont la position GPS), puis envoi sur le disque privé.
 */
class PartyMemoryPhotos extends Command
{
    /**
     * @var string
     */
    protected $signature = 'party:memory-photos
        {directory : Dossier local contenant les photos (jpg, jpeg, png, webp), triées par nom}
        {--size=400 : Hauteur de la photo en pixels (largeur = 3/4)}
        {--disk= : Disque cible (par défaut config party.memory_photos_disk, ex: s3)}';

    /**
     * @var string
     */
    protected $description = 'Prépare et envoie les photos du memory sur le disque privé (remplace les précédentes)';

    public const MAX_PHOTOS = 10;

    /** Rapport largeur / hauteur des photos, proche de celui des cartes sur téléphone. */
    public const ASPECT_RATIO = 3 / 4;

    public function handle(MemoryPhotos $photos): int
    {
        $directory = (string) $this->argument('directory');
        $size = max(100, (int) $this->option('size'));

        if (filled($this->option('disk'))) {
            config(['party.memory_photos_disk' => $this->option('disk')]);
        }

        if (! File::isDirectory($directory)) {
            $this->error("Dossier introuvable : {$directory}");

            return self::FAILURE;
        }

        $files = collect(File::files($directory))
            ->filter(fn ($file) => in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'webp'], true))
            ->sortBy(fn ($file) => $file->getFilename(), SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        if ($files->isEmpty()) {
            $this->error('Aucune photo trouvée (jpg, jpeg, png, webp).');

            return self::FAILURE;
        }

        if ($files->count() > self::MAX_PHOTOS) {
            $this->warn('Plus de '.self::MAX_PHOTOS.' photos : seules les '.self::MAX_PHOTOS.' premières (par nom) sont utilisées.');
            $files = $files->take(self::MAX_PHOTOS);
        }

        $photos->clear();

        foreach ($files as $index => $file) {
            $id = $index + 1;
            $photos->store($id, $this->prepare($file->getPathname(), $size));
            $this->line("  {$id}. {$file->getFilename()}");
        }

        $this->info("{$files->count()} photo(s) envoyée(s) sur le disque « ".config('party.memory_photos_disk').' ».');

        return self::SUCCESS;
    }

    private function prepare(string $path, int $height): string
    {
        $image = imagecreatefromstring((string) file_get_contents($path));

        if (! $image instanceof GdImage) {
            throw new \RuntimeException("Image illisible : {$path}");
        }

        $image = $this->applyExifOrientation($image, $path);

        $sourceWidth = imagesx($image);
        $sourceHeight = imagesy($image);

        // Plus grand rectangle 3:4 centré dans la photo d'origine.
        $cropWidth = (int) min($sourceWidth, round($sourceHeight * self::ASPECT_RATIO));
        $cropHeight = (int) min($sourceHeight, round($cropWidth / self::ASPECT_RATIO));
        $width = (int) round($height * self::ASPECT_RATIO);

        $portrait = imagecreatetruecolor($width, $height);
        imagecopyresampled(
            $portrait,
            $image,
            0,
            0,
            intdiv($sourceWidth - $cropWidth, 2),
            intdiv($sourceHeight - $cropHeight, 2),
            $width,
            $height,
            $cropWidth,
            $cropHeight,
        );

        ob_start();
        imagejpeg($portrait, null, 82);

        return (string) ob_get_clean();
    }

    /**
     * Les téléphones enregistrent souvent l'image couchée avec une consigne de rotation EXIF :
     * on l'applique avant de jeter les métadonnées.
     */
    private function applyExifOrientation(GdImage $image, string $path): GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated instanceof GdImage ? $rotated : $image;
    }
}
