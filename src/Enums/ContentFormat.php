<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Enums;

/**
 * How an article's body is stored — and therefore how it has to be rendered.
 *
 * Recorded **per article**, not per installation, and that is the whole point.
 * A base that switches its default editor next year must keep rendering the
 * hundred pages written before the switch; a global setting would reinterpret
 * them all at once, and JSON blocks read as markdown come out as gibberish.
 *
 * Adding a format means adding a case, a renderer and an editor driver — the
 * three together, which is why they share this vocabulary.
 */
enum ContentFormat: string
{
    /** Plain markdown. Diffable, portable, survives every editor change. */
    case Markdown = 'markdown';

    /** HTML from a rich-text editor (TipTap and friends). Sanitised on render. */
    case RichText = 'rich-text';

    /** An ordered list of typed blocks as JSON. Rendered by per-type partials. */
    case Blocks = 'blocks';

    public function label(): string
    {
        return __('knowledge-base::kb.format.'.$this->value);
    }

    /** Is the stored value JSON rather than a string? */
    public function isStructured(): bool
    {
        return $this === self::Blocks;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
