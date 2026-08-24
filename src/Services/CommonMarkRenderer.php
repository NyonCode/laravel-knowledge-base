<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Services;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;
use NyonCode\KnowledgeBase\Contracts\ContentRenderer;
use NyonCode\KnowledgeBase\Contracts\MarkdownRenderer;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Support\Html;
use NyonCode\KnowledgeBase\Support\Markdown\FencedCodeRenderer;

/**
 * The shipped renderer: CommonMark, then heading ids, then a sanitiser.
 *
 * Order matters and is not interchangeable. The ids are added by us, so
 * sanitising runs **after** — whatever an author typed (markdown allows raw
 * HTML and the converter passes it through) gets cleaned, while the ids we
 * just generated survive. Sanitising first would clean the input and then let
 * the renderer add unchecked markup on top.
 *
 * Authors are trusted people, which is exactly the argument that gets skipped:
 * an account is borrowed, an import brings in someone else's file, and the
 * output is echoed unescaped into every reader's page.
 *
 * CommonMark's own permalink extension is deliberately **not** used: it puts
 * its id on an anchor *inside* the heading and leaves a `#` in the text, so
 * the table of contents ends up linking to nothing and every entry reads
 * "Heading#". Ids belong on the heading, and {@see Html} owns them for all
 * three formats.
 */
final class CommonMarkRenderer implements ContentRenderer, MarkdownRenderer
{
    private ?MarkdownConverter $converter = null;

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

        // Kotva z `HeadingPermalink` nese `id` **uvnitř** nadpisu, ne na něm,
        // takže bez tohohle kroku nemá obsah stránky na co odkazovat – a
        // vypadá to jako článek bez nadpisů, ne jako chyba.
        return Html::sanitize(Html::withHeadingIds($html));
    }

    public function tableOfContents(string $html): array
    {
        return Html::headings($html);
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter !== null) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new TableExtension);
        $environment->addExtension(new AutolinkExtension);

        // Vlastní render code fence: z info stringu si odnese i titulek
        // (```` ```php title="app/Foo.php" ````), který výchozí render zahazuje,
        // a popíše blok stejnými `data-*` jako blokový editor — jeden tvar pro
        // zvýrazňovač i pro CSS bez ohledu na to, čím se článek psal.
        $environment->addRenderer(FencedCode::class, new FencedCodeRenderer, 10);

        return $this->converter = new MarkdownConverter($environment);
    }
}
