<?php

namespace App\Services\Party;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

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
     * @return list<int>
     */
    public function ids(): array
    {
        return collect($this->disk()->files(self::DIRECTORY))
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
