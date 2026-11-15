<?php

namespace App\Services\Party;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Photos du memory : stockées sur un disque privé (bucket S3 en prod), jamais exposées publiquement.
 * Fichiers nommés party/memory/{id}.jpg, id à partir de 1.
 */
class MemoryPhotos
{
    public const DIRECTORY = 'party/memory';

    public function disk(): Filesystem
    {
        return Storage::disk(config('party.memory_photos_disk'));
    }

    /**
     * Les photos sont optionnelles : si le disque est mal configuré ou indisponible,
     * on le signale dans les logs et le memory reste jouable en pixel art.
     *
     * @return list<int>
     */
    public function ids(): array
    {
        try {
            $files = $this->disk()->files(self::DIRECTORY);
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }

        return collect($files)
            ->map(fn (string $path) => preg_match('#/(\d+)\.jpg$#', $path, $matches) ? (int) $matches[1] : null)
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function path(int $id): string
    {
        return self::DIRECTORY."/{$id}.jpg";
    }

    public function exists(int $id): bool
    {
        return $this->disk()->exists($this->path($id));
    }

    public function store(int $id, string $jpegBinary): void
    {
        $this->disk()->put($this->path($id), $jpegBinary);
    }

    public function clear(): void
    {
        $this->disk()->deleteDirectory(self::DIRECTORY);
    }
}
