<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Services\Renderers;

use NyonCode\KnowledgeBase\Contracts\ContentRenderer;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Support\Html;

/**
 * HTML straight out of a rich-text editor.
 *
 * There is no parsing step here, which makes sanitising the *entire* job:
 * TipTap output is generated in the browser, so it is user input no matter how
 * trusted the author is — a borrowed session or a paste from a crafted page
 * both arrive as markup.
 *
 * Headings get ids on the way through. TipTap does not emit them and the table
 * of contents needs somewhere to point.
 */
final class RichTextRenderer implements ContentRenderer
{
    public function supports(ContentFormat $format): bool
    {
        return $format === ContentFormat::RichText;
    }

    public function render(string|array $content): string
    {
        if (is_array($content)) {
            return '';
        }

        return Html::sanitize(Html::withHeadingIds($content));
    }

    public function tableOfContents(string $html): array
    {
        return Html::headings($html);
    }
}
