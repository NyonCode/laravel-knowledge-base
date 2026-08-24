<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Contracts;

/**
 * Markdown in, safe HTML out.
 *
 * Behind a contract because syntax highlighting is the one thing every host
 * does differently — Torchlight here, Shiki there, nothing at all in a plain
 * install — and because the output is written straight into a page. The
 * shipped implementation sanitises; a replacement that forgets to is a stored
 * XSS in every article, so implementors are told twice.
 */
interface MarkdownRenderer
{
    /**
     * Render article markdown.
     *
     * The result is trusted downstream and echoed unescaped, so the
     * implementation — not its caller — owns sanitising it.
     */
    public function render(string $markdown): string;

    /**
     * Headings of the rendered HTML, in document order, for the table of
     * contents.
     *
     * Derived from the render rather than the markdown so anchors cannot drift
     * from the ids that actually ended up on the page.
     *
     * @return array<int, array{level: int, id: string, title: string}>
     */
    public function tableOfContents(string $html): array;
}
