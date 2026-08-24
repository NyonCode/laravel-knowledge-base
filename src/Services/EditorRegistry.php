<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Services;

use Illuminate\Support\Collection;
use NyonCode\KnowledgeBase\Contracts\EditorDriver;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Support\Settings;
use RuntimeException;

/**
 * Which writing surfaces this installation offers.
 *
 * Built from `knowledge-base.editors.drivers`, filtered by availability, so a
 * driver whose JavaScript was never bundled never appears in the picker.
 */
final class EditorRegistry
{
    /** @var Collection<string, EditorDriver> */
    private Collection $drivers;

    /** @param  iterable<EditorDriver>  $drivers */
    public function __construct(iterable $drivers = [])
    {
        $this->drivers = collect(iterator_to_array(
            is_array($drivers) ? new \ArrayIterator($drivers) : $drivers
        ))->mapWithKeys(
            static fn (EditorDriver $driver) => [$driver->name() => $driver]
        );
    }

    /** @return Collection<string, EditorDriver> */
    public function available(): Collection
    {
        return $this->drivers->filter(
            static fn (EditorDriver $driver) => $driver->isAvailable()
        );
    }

    public function get(string $name): ?EditorDriver
    {
        $driver = $this->drivers->get($name);

        return $driver?->isAvailable() === true ? $driver : null;
    }

    /**
     * The driver that can edit an article written in this format.
     *
     * Asked by format rather than by name so an article opens in an editor
     * that understands it, even when the installation's default has changed
     * since it was written.
     */
    public function forFormat(ContentFormat $format): EditorDriver
    {
        $driver = $this->drivers->first(
            static fn (EditorDriver $d) => $d->format() === $format && $d->isAvailable()
        );

        return $driver ?? $this->default();
    }

    public function default(): EditorDriver
    {
        $name = Settings::string('editors.default', 'markdown');

        return $this->get($name)
            ?? $this->available()->first()
            ?? throw new RuntimeException('No knowledge base editor driver is available.');
    }
}
