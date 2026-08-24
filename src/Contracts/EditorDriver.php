<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Contracts;

use NyonCode\KnowledgeBase\Enums\ContentFormat;

/**
 * The writing surface.
 *
 * Three ship with the package — markdown, rich text, blocks — and an app that
 * wants a fourth writes one class and adds a config line. Nothing else in the
 * base knows which one is in use: the editor component asks the driver what to
 * render, the driver says which {@see ContentFormat} it produces, and the
 * matching {@see ContentRenderer} takes it from there.
 *
 * Deliberately not "one editor with options". An editor is a component, a
 * storage format and a renderer that have to agree; bundling the three behind
 * one interface is what makes swapping them a config change instead of a
 * migration.
 */
interface EditorDriver
{
    /** Stable key used in config and stored nowhere. */
    public function name(): string;

    /** Shown to whoever picks an editor. */
    public function label(): string;

    /** What this driver writes into `articles.body`. */
    public function format(): ContentFormat;

    /**
     * Blade view rendering the writing surface.
     *
     * Receives `$statePath` (the Livewire property holding the body) and
     * `$article`. It must bind to that property and nothing else — the editor
     * component owns saving, validation and revisions.
     */
    public function view(): string;

    /**
     * Is it usable in this installation?
     *
     * A driver whose JavaScript was never bundled must say so rather than
     * render an inert box: an editor that silently does not work is worse than
     * one that is missing from the list.
     */
    public function isAvailable(): bool;
}
