<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Editors;

use NyonCode\KnowledgeBase\Contracts\EditorDriver;
use NyonCode\KnowledgeBase\Enums\ContentFormat;

/**
 * A textarea and a preview tab.
 *
 * The default, and the one to keep unless there is a reason not to: markdown
 * needs no JavaScript, diffs cleanly between revisions, survives every future
 * editor change and can be written by someone in a terminal. The other two
 * drivers exist for authors who will not write markdown — which is a real
 * reason, just not the default one.
 */
final class MarkdownEditor implements EditorDriver
{
    public function name(): string
    {
        return 'markdown';
    }

    public function label(): string
    {
        return __('knowledge-base::kb.format.markdown');
    }

    public function format(): ContentFormat
    {
        return ContentFormat::Markdown;
    }

    public function view(): string
    {
        return 'knowledge-base::editors.markdown';
    }

    /** Nothing to install — this is why it is the fallback. */
    public function isAvailable(): bool
    {
        return true;
    }
}
