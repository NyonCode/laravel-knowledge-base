<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Support\Markdown;

use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\HtmlElement;
use League\CommonMark\Util\Xml;
use NyonCode\KnowledgeBase\Support\Html;
use Stringable;

/**
 * Render code fence, který si z info stringu odnese i **titulek bloku**.
 *
 * Výchozí render z ```` ```php title="app/Models/User.php" ```` použije jen
 * první slovo (`language-php`) a zbytek zahodí. U ukázky kódu je přitom název
 * souboru půlka informace: čtenář musí vědět, *kam* se to píše, ne jen *co*
 * se píše.
 *
 * Výstup je stejný popis bloku, jaký vydává `code` blok blokového editoru —
 * schválně, aby zvýrazňovač hostitele i CSS měly jeden tvar bez ohledu na to,
 * kterým editorem se článek psal:
 *
 *     <pre data-lang="php" data-title="app/Models/User.php">
 *         <code class="language-php">…</code>
 *     </pre>
 *
 * `data-*` na `<pre>` přežije sanitizaci ({@see Html}).
 *
 * Podporovaný zápis (jazyk je vždy první):
 *
 *     ```php title="app/Models/User.php"
 *     ```php filename=config/app.php
 *     ```bash
 */
final class FencedCodeRenderer implements NodeRendererInterface
{
    public function render(
        Node $node,
        ChildNodeRendererInterface $childRenderer
    ): Stringable {
        FencedCode::assertInstanceOf($node);

        /** @var FencedCode $node */
        $info = (string) ($node->getInfo() ?? '');

        $language = $this->language($info);
        $title = $this->title($info);

        $codeAttrs = $node->data->getData('attributes');

        if ($language !== null) {
            $codeAttrs->append('class', 'language-'.$language);
        }

        /** @var array<string, string> $preAttrs */
        $preAttrs = [];

        if ($language !== null) {
            $preAttrs['data-lang'] = $language;
        }

        if ($title !== null) {
            $preAttrs['data-title'] = $title;
        }

        /** @var array<string, array<string>|bool|string> $codeAttributes */
        $codeAttributes = $codeAttrs->export();

        return new HtmlElement(
            'pre',
            $preAttrs,
            new HtmlElement(
                'code',
                $codeAttributes,
                Xml::escape($node->getLiteral())
            )
        );
    }

    /**
     * Jazyk = první slovo info stringu (`php`, `bash`, `blade`).
     */
    private function language(string $info): ?string
    {
        if (preg_match('/^\s*([A-Za-z0-9#+._-]+)/', $info, $m) !== 1) {
            return null;
        }

        // `language-php` i `php` píšou lidé oboje; třídu doplňujeme sami.
        return preg_replace('/^language-/', '', $m[1]);
    }

    /**
     * Titulek bloku z `title=` / `filename=` (s uvozovkami i bez nich).
     */
    private function title(string $info): ?string
    {
        $matched = preg_match(
            '/\b(?:title|filename)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))/i',
            $info,
            $m
        );

        if ($matched !== 1) {
            return null;
        }

        // Nezachycená skupina chybí úplně, prázdná je ''. Bereme první, která
        // něco nese – podle toho, jestli byl titulek v uvozovkách, nebo ne.
        $title = trim($m[1].($m[2] ?? '').($m[3] ?? ''));

        return $title === '' ? null : $title;
    }
}
