<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Editors;

use NyonCode\KnowledgeBase\Contracts\EditorDriver;
use NyonCode\KnowledgeBase\Enums\ContentFormat;

/**
 * A TipTap surface, for authors who will not write markdown.
 *
 * The package ships the Blade side and the Alpine glue but **not** TipTap
 * itself: bundling an editor into a package means shipping a second copy of
 * ProseMirror into apps that already have one, and pinning its version for
 * everybody. The host bundles it and exposes `window.kbEditor`.
 *
 * Until it does, {@see isAvailable()} answers false and the driver is not
 * offered — an editor that renders an inert box is worse than one that is
 * absent, because the author only finds out after writing a page into it.
 */
final class RichTextEditor implements EditorDriver
{
    public function name(): string
    {
        return 'tiptap';
    }

    public function label(): string
    {
        return __('knowledge-base::kb.format.rich-text');
    }

    public function format(): ContentFormat
    {
        return ContentFormat::RichText;
    }

    public function view(): string
    {
        return 'knowledge-base::editors.rich-text';
    }

    public function isAvailable(): bool
    {
        // The host says so explicitly rather than the package guessing from an
        // asset path — the bundle can live anywhere, or be provided by a CDN.
        return (bool) config('knowledge-base.editors.tiptap.bundled', false);
    }
}
