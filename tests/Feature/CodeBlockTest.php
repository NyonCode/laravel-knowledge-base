<?php

declare(strict_types=1);

use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Livewire\Admin\ArticleEditor;
use NyonCode\KnowledgeBase\Services\RendererRegistry;

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
