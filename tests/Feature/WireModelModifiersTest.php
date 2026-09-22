<?php

declare(strict_types=1);

/*
 * Livewire 4 rozdělil modifikátory `wire:model` na dvě vrstvy: co stojí před
 * `.live`, řídí jen synchronizaci na klientu, a teprve to za `.live` posílá
 * request. Samotné `wire:model.blur` tak na server nepošle nic — blok editoru
 * se neuloží ani nepřekreslí, a protože `Livewire::test()->set()` JS obchází,
 * žádný jiný test si toho nevšimne. `wire:model.live.blur` se chová stejně
 * v Livewire 3 i 4.
 */

it('neposílá pole do šablon s modifikátorem, který v Livewire 4 nemluví se serverem', function () {
    $offenders = collect(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 2).'/resources/views')))
        ->filter(fn (SplFileInfo $file) => str_ends_with($file->getFilename(), '.blade.php'))
        ->flatMap(fn (SplFileInfo $file) => collect(file($file->getPathname()))
            ->filter(fn (string $line) => preg_match('/wire:model\.(blur|change)\b/', $line) === 1)
            ->keys()
            ->map(fn (int $line) => $file->getFilename().':'.($line + 1)))
        ->values()
        ->all();

    expect($offenders)->toBe([]);
});
