<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Services\Renderers;

use Illuminate\Contracts\View\Factory as ViewFactory;
use NyonCode\KnowledgeBase\Contracts\ContentRenderer;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Support\Html;

/**
 * An ordered list of typed blocks, each rendered by its own Blade partial.
 *
 * The canonical shape is `['type' => 'heading', 'data' => [...]]` — the same
 * one the mail builder in the host app uses, on purpose: a block format that
 * differs per feature means two normalisers and two sets of partials.
 *
 * An unknown type renders **nothing** rather than throwing. Blocks outlive the
 * code that wrote them: a page authored with a block someone later removed
 * must still render its other twelve blocks, not a stack trace.
 */
final class BlockRenderer implements ContentRenderer
{
    public function __construct(private readonly ViewFactory $views) {}

    public function supports(ContentFormat $format): bool
    {
        return $format === ContentFormat::Blocks;
    }

    public function render(string|array $content): string
    {
        $blocks = is_string($content)
            ? (json_decode($content, true) ?: [])
            : $content;

        if (! is_array($blocks)) {
            return '';
        }

        $html = '';

        foreach ($blocks as $block) {
            if (! is_array($block) || blank($block['type'] ?? null)) {
                continue;
            }

            $view = 'knowledge-base::blocks.'.(string) $block['type'];

            if (! $this->views->exists($view)) {
                continue;
            }

            $html .= $this->views->make($view, [
                'data' => (array) ($block['data'] ?? []),
            ])->render();
        }

        // Partials are ours, but a block's *data* is authored text and lands
        // inside them — so the assembled page still goes through the sanitiser.
        return Html::sanitize(Html::withHeadingIds($html));
    }

    public function tableOfContents(string $html): array
    {
        return Html::headings($html);
    }
}
