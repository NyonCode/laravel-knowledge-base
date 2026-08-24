<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NyonCode\KnowledgeBase\Contracts\ImageLibrary;
use NyonCode\KnowledgeBase\Support\Settings;

/**
 * Obrázky na disku z konfigurace.
 *
 * Výchozí implementace: žádná tabulka, žádná metadata — adresář a `Storage`.
 * Pro znalostní bázi to stačí, protože obrázek v návodu je ilustrace ke
 * konkrétní stránce, ne majetek, který se spravuje sám o sobě.
 */
final class DiskImageLibrary implements ImageLibrary
{
    public function store(UploadedFile $file): string
    {
        $path = $file->store($this->directory(), $this->diskName());

        return $this->disk()->url((string) $path);
    }

    public function recent(int $limit = 60): array
    {
        $disk = $this->disk();
        $directory = $this->directory();

        if (! $disk->exists($directory)) {
            return [];
        }

        $files = collect($disk->files($directory))
            ->filter(fn (string $path): bool => in_array(
                mb_strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg'],
                true
            ));

        // Řadí se podle času zápisu, ne podle jména: „ten, co jsem před chvílí
        // nahrál" je jediný důvod, proč se do galerie kouká.
        return $files
            ->sortByDesc(fn (string $path): int => $disk->lastModified($path))
            ->take($limit)
            ->map(fn (string $path): array => [
                'url' => $disk->url($path),
                'name' => basename($path),
            ])
            ->values()
            ->all();
    }

    private function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    private function diskName(): string
    {
        return Settings::string('images.disk', 'public');
    }

    private function directory(): string
    {
        return Settings::string('images.directory', 'images');
    }
}
