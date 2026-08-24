<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Services;

use NyonCode\KnowledgeBase\Contracts\ContentRenderer;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use RuntimeException;

/**
 * Picks the renderer that matches how an article was written.
 *
 * Resolution is by {@see ContentFormat}, never by configuration, so a page
 * authored in one editor keeps rendering correctly after the default editor
 * changes. A format with no renderer throws rather than returning empty: a
 * page that silently renders blank looks like a lost article, and somebody
 * rewrites it instead of registering the missing renderer.
 */
final class RendererRegistry
{
    /** @var array<int, ContentRenderer> */
    private array $renderers = [];

    /** @param  iterable<ContentRenderer>  $renderers */
    public function __construct(iterable $renderers = [])
    {
        foreach ($renderers as $renderer) {
            $this->register($renderer);
        }
    }

    public function register(ContentRenderer $renderer): self
    {
        // Newest wins: a host binding its own markdown renderer registers
        // after ours and takes over without unregistering anything.
        array_unshift($this->renderers, $renderer);

        return $this;
    }

    public function for(ContentFormat $format): ContentRenderer
    {
        foreach ($this->renderers as $renderer) {
            if ($renderer->supports($format)) {
                return $renderer;
            }
        }

        throw new RuntimeException(
            "No content renderer is registered for the [{$format->value}] format."
        );
    }

    /** @param  string|array<array-key, mixed>  $content */
    public function render(ContentFormat $format, string|array $content): string
    {
        return $this->for($format)->render($content);
    }
}
