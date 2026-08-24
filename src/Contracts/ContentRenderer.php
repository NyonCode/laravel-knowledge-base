<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Contracts;

use NyonCode\KnowledgeBase\Enums\ContentFormat;

/**
 * Turns whatever an editor stored into the HTML a reader gets.
 *
 * One renderer per {@see ContentFormat}, resolved through the registry, so an
 * article always renders with the renderer that matches how it was written —
 * never with whatever the installation happens to prefer today.
 *
 * The output is echoed unescaped into every reader's page, so each
 * implementation — not its caller — owns sanitising it. A rich-text renderer
 * that trusts its input is a stored XSS in every article; the markdown one
 * has the same duty, because markdown allows raw HTML.
 */
interface ContentRenderer
{
    public function supports(ContentFormat $format): bool;

    /**
     * @param  string|array<array-key, mixed>  $content  markdown, HTML, or decoded blocks
     */
    public function render(string|array $content): string;

    /**
     * Headings of the rendered HTML, in document order.
     *
     * Read off the render rather than the source: the ids in the page are the
     * only ones the contents links can point at, and a second slugger drifts.
     *
     * @return array<int, array{level: int, id: string, title: string}>
     */
    public function tableOfContents(string $html): array;
}
