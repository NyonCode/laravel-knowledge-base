<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use NyonCode\KnowledgeBase\Contracts\ContentRenderer;
use NyonCode\KnowledgeBase\Contracts\MarkdownRenderer;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * The shipped renderer: CommonMark, heading anchors, then a sanitiser.
 *
 * Order matters and is not interchangeable. Anchors are added by the converter
 * and are therefore ours; sanitising runs **after** so that whatever an author
 * typed — markdown allows raw HTML and the converter passes it through — is
 * cleaned, while the ids we just generated survive. Sanitising first would
 * clean the input and then let the renderer add unchecked markup on top.
 *
 * Authors are trusted people, which is exactly the argument that gets skipped:
 * an account is borrowed, an import brings in someone else's file, and the
 * output is echoed unescaped into every reader's page.
 */
final class CommonMarkRenderer implements ContentRenderer, MarkdownRenderer
{
    private ?MarkdownConverter $converter = null;

    private ?HtmlSanitizer $sanitizer = null;

    public function supports(ContentFormat $format): bool
    {
        return $format === ContentFormat::Markdown;
    }

    /** @param  string|array<array-key, mixed>  $content */
    public function render(string|array $content): string
    {
        // Blocks never reach here; a defensive cast beats a TypeError in a view.
        $markdown = is_array($content) ? '' : $content;

        $html = $this->converter()->convert($markdown)->getContent();

        return $this->sanitizer()->sanitize($html);
    }

    public function tableOfContents(string $html): array
    {
        // Read off the render, not the markdown: the ids in the page are the
        // only ones the links can point at, and a second slugger would drift.
        preg_match_all(
            '/<h([23])[^>]*\bid="([^"]+)"[^>]*>(.*?)<\/h\1>/s',
            $html,
            $matches,
            PREG_SET_ORDER
        );

        return array_map(
            static fn (array $m): array => [
                'level' => (int) $m[1],
                'id' => $m[2],
                'title' => trim(html_entity_decode(
                    strip_tags($m[3]),
                    ENT_QUOTES | ENT_HTML5
                )),
            ],
            $matches
        );
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter !== null) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'html_class' => 'kb-anchor',
                'id_prefix' => '',
                'fragment_prefix' => '',
                'insert' => 'after',
                'title' => '',
                'symbol' => '#',
                'aria_hidden' => true,
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new AutolinkExtension);
        $environment->addExtension(new HeadingPermalinkExtension);

        return $this->converter = new MarkdownConverter($environment);
    }

    private function sanitizer(): HtmlSanitizer
    {
        if ($this->sanitizer !== null) {
            return $this->sanitizer;
        }

        $config = (new HtmlSanitizerConfig)
            ->allowSafeElements()
            ->allowElement('h2', ['id', 'class'])
            ->allowElement('h3', ['id', 'class'])
            ->allowElement('h4', ['id', 'class'])
            ->allowElement('table', ['class'])
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('th', ['align', 'colspan', 'rowspan'])
            ->allowElement('td', ['align', 'colspan', 'rowspan'])
            ->allowElement('pre', ['class'])
            ->allowElement('code', ['class'])
            ->allowElement('span', ['class', 'style'])
            ->allowElement('div', ['class'])
            ->allowElement('a', ['href', 'title', 'class', 'id', 'target', 'rel'])
            ->allowElement('img', ['src', 'alt', 'title', 'width', 'height', 'loading'])
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowMediaSchemes(['https', 'http', 'data']);

        return $this->sanitizer = new HtmlSanitizer($config);
    }
}
