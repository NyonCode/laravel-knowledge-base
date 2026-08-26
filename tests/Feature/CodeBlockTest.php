<?php

declare(strict_types=1);

use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Livewire\Admin\ArticleEditor;
use NyonCode\KnowledgeBase\Services\RendererRegistry;
use NyonCode\KnowledgeBase\Support\CodeComment;

/*
 * Ukázka kódu popisuje sama sebe — jazyk, název souboru a volby jednoho bloku
 * — a ten popis musí přežít až do HTML. Zvýrazňovač dodává hostitel a čte si ho
 * z `data-*` na `<pre>`; když se cestou ztratí, blok se **vykreslí dál**, jen
 * černobíle a bez hlavičky, takže porucha vypadá jako rozhodnutí.
 */

function renderBlocks(array $blocks): string
{
    return app(RendererRegistry::class)->render(ContentFormat::Blocks, $blocks);
}

it('describes a code block for the highlighter', function () {
    $html = renderBlocks([[
        'type' => 'code',
        'data' => [
            'language' => 'php',
            'title' => 'app/Models/User.php',
            'text' => 'echo 1;',
        ],
    ]]);

    expect($html)
        ->toContain('data-lang="php"')
        ->toContain('data-title="app/Models/User.php"')
        ->toContain('class="language-php"');
});

it('keeps the description through sanitising', function () {
    // Sanitizér povoluje atributy výčtem, ne vzorem — `data-*` se z něj dá
    // vypustit jedním smazaným řádkem a nic nespadne.
    $html = renderBlocks([[
        'type' => 'code',
        'data' => ['language' => 'php', 'title' => 'config/app.php', 'text' => 'return [];'],
    ]]);

    expect(strip_tags($html, '<pre>'))->toContain('data-title');
});

it('sends per-block options only when the author changed one', function () {
    $plain = renderBlocks([[
        'type' => 'code',
        'data' => ['language' => 'php', 'text' => 'echo 1;'],
    ]]);

    $numbered = renderBlocks([[
        'type' => 'code',
        'data' => ['language' => 'php', 'text' => 'echo 1;', 'line_numbers' => true],
    ]]);

    expect($plain)->not->toContain('data-torchlight')
        ->and(html_entity_decode($numbered))->toContain('"lineNumbers":true');
});

it('marks the terminal block with a data attribute, not a class', function () {
    // Zvýrazňovač vrací vlastní `<pre>` a přenáší přes něj jen `data-*`;
    // třída by se ztratila a terminál by vypadal jako obyčejný blok.
    $html = renderBlocks([[
        'type' => 'terminal',
        'data' => ['text' => 'php artisan migrate'],
    ]]);

    expect($html)
        ->toContain('data-kb-block="terminal"')
        ->toContain('class="language-bash"');
});

it('carries a fence title from markdown into the block description', function () {
    $html = app(RendererRegistry::class)->render(
        ContentFormat::Markdown,
        "```php title=\"app/Foo.php\"\nreturn [];\n```"
    );

    expect($html)
        ->toContain('data-lang="php"')
        ->toContain('data-title="app/Foo.php"');
});

it('gives a new code block a language and honest checkboxes', function () {
    // Chybějící volba znamená „nech to na globálním nastavení", jenže prázdný
    // checkbox tvrdí „vypnuto" — a u diff značek, které jsou zapnuté, by panel
    // ukazoval pravý opak toho, co stránka dělá.
    $editor = new class extends ArticleEditor
    {
        /** @return array<int, array<string, mixed>> */
        public function rows(): array
        {
            return $this->blockData;
        }
    };

    $editor->addBlock('code');

    expect($editor->rows()[0])
        ->toMatchArray([
            'type' => 'code',
            'language' => 'php',
            'line_numbers' => false,
            'diff_indicators' => true,
        ]);
});

it('fills the checkboxes in for a block written before they existed', function () {
    $editor = new class extends ArticleEditor
    {
        /** @return array<int, array<string, mixed>> */
        public static function unpack(string $json): array
        {
            return self::toEditing($json);
        }
    };

    $rows = $editor::unpack((string) json_encode([[
        'type' => 'code',
        'data' => ['language' => 'php', 'text' => 'echo 1;'],
    ]]));

    expect($rows[0]['diff_indicators'])->toBeTrue()
        ->and($rows[0]['line_numbers'])->toBeFalse();
});

it('does not invent a language for a block saved without one', function () {
    // U uloženého bloku je prázdno **volba autora** (ukázka bez zvýraznění),
    // ne chybějící údaj — doplnit tam jazyk by mu přebarvilo text.
    $editor = new class extends ArticleEditor
    {
        /** @return array<int, array<string, mixed>> */
        public static function unpack(string $json): array
        {
            return self::toEditing($json);
        }
    };

    $rows = $editor::unpack((string) json_encode([[
        'type' => 'code',
        'data' => ['text' => 'nevím čím to je'],
    ]]));

    expect($rows[0])->not->toHaveKey('language');
});

it('previews a code block through the very pipeline the page uses', function () {
    // Ne přibližný náhled: zvýraznění, anotace i volby bloku vyhodnocuje až
    // server, takže napodobenina by lhala právě v tom, kvůli čemu se do
    // náhledu člověk dívá.
    $editor = new class extends ArticleEditor
    {
        /** @param  array<int, array<string, mixed>>  $rows */
        public function seed(array $rows): void
        {
            $this->blockData = $rows;
        }
    };

    $editor->seed([[
        'type' => 'code',
        'language' => 'php',
        'title' => 'app/Foo.php',
        'text' => 'echo 1;',
        'line_numbers' => true,
    ]]);

    expect($editor->previewFor(0))
        ->toContain('data-lang="php"')
        ->toContain('data-title="app/Foo.php"')
        ->toContain('echo 1;')
        ->and(html_entity_decode((string) $editor->previewFor(0)))
        ->toContain('"lineNumbers":true');
});

it('shows no preview for an empty block or a block of another type', function () {
    // Rámeček s ničím uvnitř jen zabírá místo.
    $editor = new class extends ArticleEditor
    {
        /** @param  array<int, array<string, mixed>>  $rows */
        public function seed(array $rows): void
        {
            $this->blockData = $rows;
        }
    };

    $editor->seed([
        ['type' => 'code', 'language' => 'php', 'text' => '   '],
        ['type' => 'text', 'text' => 'obyčejný odstavec'],
    ]);

    expect($editor->previewFor(0))->toBeNull()
        ->and($editor->previewFor(1))->toBeNull()
        ->and($editor->previewFor(99))->toBeNull();
});

it('keeps the editor standing when the highlighter fails', function () {
    // Náhled je pohodlí, ne obsah: zvýrazňovač volá cizí službu a ta umí být
    // nedostupná i nenastavená. Psát se musí dát dál i bez obrázku výsledku.
    app()->bind(RendererRegistry::class, function (): RendererRegistry {
        throw new RuntimeException('zvýrazňovač je mimo');
    });

    $editor = new class extends ArticleEditor
    {
        /** @param  array<int, array<string, mixed>>  $rows */
        public function seed(array $rows): void
        {
            $this->blockData = $rows;
        }
    };

    $editor->seed([['type' => 'code', 'language' => 'php', 'text' => 'echo 1;']]);

    expect($editor->previewFor(0))->toBeNull();
});

it('stays quiet about options the author left alone', function () {
    // Volby jednoho bloku se zvýrazňovači předávají servisním komentářem před
    // kódem. Ten se v ukázce ukáže pokaždé, když ho zvýrazňovač nesní — blok,
    // kde autor nic nepřepnul, si to riziko nemá proč kupovat.
    $defaults = config('knowledge-base.editors.blocks.code');

    $untouched = renderBlocks([[
        'type' => 'code',
        'data' => [
            'language' => 'php',
            'text' => 'echo 1;',
            'line_numbers' => $defaults['line_numbers'],
            'diff_indicators' => $defaults['diff_indicators'],
        ],
    ]]);

    $changed = renderBlocks([[
        'type' => 'code',
        'data' => [
            'language' => 'php',
            'text' => 'echo 1;',
            'line_numbers' => ! $defaults['line_numbers'],
            'diff_indicators' => $defaults['diff_indicators'],
        ],
    ]]);

    expect($untouched)->not->toContain('data-torchlight')
        // A pošle se jen ta jedna, co se liší — ne obě.
        ->and(html_entity_decode($changed))->toContain('"lineNumbers"')
        ->and(html_entity_decode($changed))->not->toContain('"diffIndicators"');
});

it('spells the annotation the way the chosen language wants it', function () {
    // Anotace musí být **skutečný komentář** jazyka bloku, jinak se nezpracuje
    // a zůstane v ukázce viset jako text — a autor to zjistí až na hotové
    // stránce. Nápověda, která ukáže obecný tvar, ho tam pošle.
    expect(CodeComment::annotation('json', '[tl! focus]'))->toBe('// [tl! focus]')
        ->and(CodeComment::annotation('bash', '[tl! focus]'))->toBe('# [tl! focus]')
        ->and(CodeComment::annotation('html', '[tl! focus]'))->toBe('<!-- [tl! focus] -->')
        ->and(CodeComment::annotation('sql', '[tl! focus]'))->toBe('-- [tl! focus]')
        ->and(CodeComment::annotation('css', '[tl! focus]'))->toBe('/* [tl! focus] */')
        // Prostý text bere zvýrazňovač celý jako komentář, takže značka navíc
        // by v ukázce zůstala vidět.
        ->and(CodeComment::annotation('text', '[tl! focus]'))->toBe('[tl! focus]')
        ->and(CodeComment::annotation(null, '[tl! focus]'))->toBe('[tl! focus]');
});
