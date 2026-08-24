<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Editors;

use NyonCode\KnowledgeBase\Contracts\EditorDriver;
use NyonCode\KnowledgeBase\Enums\ContentFormat;

/**
 * Compose a page from typed blocks.
 *
 * Worth it when pages have to look consistent — a callout is always the same
 * callout, a step list always numbers the same way — and when the people
 * writing them are not the people who own the design. The cost is that the
 * content stops being a document: it is structured data, harder to move
 * somewhere else and only as expressive as the block set.
 *
 * Structure is changed on the **server** (add, move, remove), never in
 * JavaScript alone, so the block array has exactly one owner.
 */
final class BlockEditor implements EditorDriver
{
    public function name(): string
    {
        return 'blocks';
    }

    public function label(): string
    {
        return __('knowledge-base::kb.format.blocks');
    }

    public function format(): ContentFormat
    {
        return ContentFormat::Blocks;
    }

    public function view(): string
    {
        return 'knowledge-base::editors.blocks';
    }

    public function isAvailable(): bool
    {
        return true;
    }
}
