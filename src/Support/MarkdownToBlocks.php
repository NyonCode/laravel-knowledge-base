<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Support;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Block\BlockQuote;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\Heading;
use League\CommonMark\Extension\CommonMark\Node\Block\IndentedCode;
use League\CommonMark\Extension\CommonMark\Node\Block\ListBlock;
use League\CommonMark\Extension\CommonMark\Node\Block\ListItem;
use League\CommonMark\Extension\CommonMark\Node\Block\ThematicBreak;
use League\CommonMark\Extension\Table\Table;
use League\CommonMark\Extension\Table\TableCell;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\Table\TableRow;
use League\CommonMark\Node\Block\Paragraph;
use League\CommonMark\Node\Node;
use League\CommonMark\Parser\MarkdownParser;
use League\CommonMark\Renderer\HtmlRenderer;

/**
 * Markdown → pole bloků.
 *
 * Pro převod existujícího obsahu do blokového schématu: článek napsaný
 * v markdownu se rozpadne na bloky, které jdou dál editovat v blokovém
 * editoru.
 *
 * Jde přes **strom** z CommonMarku, ne přes regulární výrazy nad řádky. Řádkový
 * parser markdownu je past: `#` uvnitř bloku kódu není nadpis a `|` v odstavci
 * není tabulka, a to se pozná až podle kontextu, který má strom a řádek ne.
 *
 * Text odstavců se převádí **s inline formátováním** (tučné, odkazy, `kód`),
 * protože prozaické bloky drží HTML. Uzel, pro který tu není blok, se
 * **nezahodí** — skončí jako text s vykresleným HTML. Ztratit odstavec při
 * migraci je horší než mít ho v obecnějším bloku.
 */
final class MarkdownToBlocks
{
    private Environment $environment;

    private MarkdownParser $parser;

    private HtmlRenderer $renderer;

    public function __construct()
    {
        $this->environment = new Environment(['html_input' => 'allow']);
        $this->environment->addExtension(new CommonMarkCoreExtension);
        $this->environment->addExtension(new TableExtension);

        $this->parser = new MarkdownParser($this->environment);
        $this->renderer = new HtmlRenderer($this->environment);
    }

    /**
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public function convert(string $markdown): array
    {
        $blocks = [];

        foreach ($this->parser->parse($markdown)->children() as $node) {
            $blocks[] = $this->block($node);
        }

        return $blocks;
    }

    /**
     * Uzel bez vlastního typu skončí jako text – nikdy se nezahazuje.
     *
     * @return array{type: string, data: array<string, mixed>}
     */
    private function block(Node $node): array
    {
        return match (true) {
            $node instanceof Heading => [
                'type' => 'heading',
                'data' => [
                    // Hlouběji než H3 blok neumí a obsah stránky by to stejně
                    // nezobrazil — spadne to na H3.
                    'level' => (string) min(3, max(2, $node->getLevel())),
                    'text' => $this->plain($node),
                ],
            ],
            $node instanceof ThematicBreak => ['type' => 'divider', 'data' => []],
            $node instanceof FencedCode => [
                'type' => 'code',
                'data' => [
                    'language' => strtok(trim($node->getInfo() ?? ''), ' ') ?: null,
                    'text' => $node->getLiteral(),
                ],
            ],
            $node instanceof IndentedCode => [
                'type' => 'code',
                'data' => ['language' => null, 'text' => $node->getLiteral()],
            ],
            $node instanceof BlockQuote => [
                'type' => 'quote',
                'data' => ['text' => $this->inner($node)],
            ],
            $node instanceof ListBlock => [
                // Číslovaný seznam je postup, odrážky jsou výčet – jsou to dva
                // různé bloky, protože se i jinak čtou.
                'type' => $node->getListData()->type === ListBlock::TYPE_ORDERED
                    ? 'steps'
                    : 'list',
                'data' => ['items' => $this->items($node)],
            ],
            $node instanceof Table => [
                'type' => 'table',
                'data' => ['rows' => $this->rows($node)],
            ],
            $node instanceof Paragraph => [
                'type' => 'text',
                'data' => ['text' => $this->inner($node)],
            ],
            default => [
                'type' => 'text',
                'data' => ['text' => trim($this->renderer->renderNodes([$node]))],
            ],
        };
    }

    /** Holý text uzlu – pro nadpisy, kde formátování nemá kam jít. */
    private function plain(Node $node): string
    {
        return trim(html_entity_decode(
            strip_tags($this->renderer->renderNodes($node->children())),
            ENT_QUOTES | ENT_HTML5
        ));
    }

    /** Vnitřek uzlu jako HTML – zachová tučné, odkazy i inline kód. */
    private function inner(Node $node): string
    {
        return trim($this->renderer->renderNodes($node->children()));
    }

    /**
     * @return array<int, string>
     */
    private function items(ListBlock $list): array
    {
        $items = [];

        foreach ($list->children() as $item) {
            if (! $item instanceof ListItem) {
                continue;
            }

            // Položka je odstavec (nebo víc); bere se jejich text, protože
            // blok seznamu nese řádky, ne strom.
            $text = trim(strip_tags(
                $this->renderer->renderNodes($item->children()),
                '<strong><em><code><a>'
            ));

            if ($text !== '') {
                $items[] = $text;
            }
        }

        return $items;
    }

    /** Tabulka jako řádky oddělené `|` – tvar, který čte blok tabulky. */
    private function rows(Table $table): string
    {
        $lines = [];

        foreach ($table->children() as $section) {
            foreach ($section->children() as $row) {
                if (! $row instanceof TableRow) {
                    continue;
                }

                $cells = [];

                foreach ($row->children() as $cell) {
                    if ($cell instanceof TableCell) {
                        $cells[] = $this->plain($cell);
                    }
                }

                $lines[] = implode(' | ', $cells);
            }
        }

        return implode("\n", $lines);
    }
}
