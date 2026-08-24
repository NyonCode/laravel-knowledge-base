<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use NyonCode\KnowledgeBase\Contracts\ImageLibrary;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Livewire\Admin\ArticleEditor;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Models\Category;
use NyonCode\KnowledgeBase\Services\CommonMarkRenderer;
use NyonCode\KnowledgeBase\Services\KnowledgeBase;
use NyonCode\KnowledgeBase\Services\RendererRegistry;
use NyonCode\KnowledgeBase\Support\MarkdownToBlocks;

/*
 * The three things that decide whether a knowledge base is trustworthy:
 * who can see what, whether search tells the truth, and whether the base
 * admits when it has gone stale.
 */

function kb(): KnowledgeBase
{
    return app(KnowledgeBase::class);
}

it('keeps internal articles away from a reader who is not signed in', function () {
    Article::factory()->create(['title' => 'Public page']);
    Article::factory()->internal()->create(['title' => 'Internal runbook']);

    $titles = kb()->find('page')->merge(kb()->find('runbook'))->pluck('title');

    expect($titles)->toContain('Public page')
        ->and($titles)->not->toContain('Internal runbook');
});

it('hides an internal article behind its slug too, and says 404 not 403', function () {
    // Answering "forbidden" would confirm the slug exists, which is usually
    // the part worth hiding.
    $article = Article::factory()->internal()->create(['slug' => 'secret-runbook']);

    expect(kb()->article($article->slug))->toBeNull();
});

it('does not list drafts to readers', function () {
    Article::factory()->draft()->create(['title' => 'Half written']);

    expect(kb()->find('Half written'))->toBeEmpty();
});

it('requires every word to match, not just one', function () {
    Article::factory()->create(['title' => 'Publishing a release', 'body' => 'steps']);
    Article::factory()->create(['title' => 'Release notes', 'body' => 'nothing about plans']);

    $titles = kb()->find('release publishing')->pluck('title');

    expect($titles)->toContain('Publishing a release')
        ->and($titles)->not->toContain('Release notes');
});

it('ranks a title hit above a body hit', function () {
    Article::factory()->create(['title' => 'Mentioned in passing', 'body' => 'webhook somewhere here']);
    Article::factory()->create(['title' => 'Webhook setup', 'body' => 'unrelated words']);

    expect(kb()->find('webhook')->first()->title)->toBe('Webhook setup');
});

it('treats a typed percent sign as a character, not a wildcard', function () {
    Article::factory()->create(['title' => 'Real page']);

    expect(kb()->find('%'))->toBeEmpty();
});

it('keeps the version that was replaced, not the one that replaced it', function () {
    $article = kb()->save(new Article, [
        'title' => 'First',
        'slug' => 'first',
        'body' => 'original body',
    ]);

    kb()->save($article, ['body' => 'rewritten body'], note: 'clarified');

    expect($article->revisions()->count())->toBe(1)
        ->and($article->revisions()->first()->body)->toBe('original body')
        ->and($article->fresh()->body)->toBe('rewritten body');
});

it('renders on write with the renderer matching the format', function () {
    $article = kb()->save(new Article, [
        'title' => 'Rendered',
        'slug' => 'rendered',
        'body' => "## Heading\n\nSome text.",
        'format' => ContentFormat::Markdown,
    ]);

    expect($article->body_html)->toContain('<h2')->toContain('Some text');
});

it('strips a script an author pasted into markdown', function () {
    $article = kb()->save(new Article, [
        'title' => 'Nasty',
        'slug' => 'nasty',
        'body' => "Hello\n\n<script>alert(1)</script>",
    ]);

    expect($article->body_html)->not->toContain('<script');
});

it('renders blocks through their partials and skips a type nobody registered', function () {
    $html = app(RendererRegistry::class)->render(ContentFormat::Blocks, [
        ['type' => 'heading', 'data' => ['text' => 'Steps']],
        ['type' => 'not-a-real-block', 'data' => ['text' => 'ignored']],
    ]);

    expect($html)->toContain('Steps')->not->toContain('ignored');
});

it('counts a stale page as stale even when it was never reviewed', function () {
    $never = Article::factory()->create(['reviewed_at' => null, 'published_at' => now()->subYears(3)]);
    $fresh = Article::factory()->create(['reviewed_at' => now()]);

    expect($never->isStale())->toBeTrue()
        ->and($fresh->isStale())->toBeFalse()
        ->and(Article::query()->stale()->pluck('id'))->toContain($never->id);
});

it('counts one vote per reader and lets them change their mind', function () {
    $article = Article::factory()->create();

    kb()->recordFeedback($article, true);
    kb()->recordFeedback($article, false, 'the second step is wrong');

    $article->refresh();

    expect($article->helpful_count)->toBe(0)
        ->and($article->unhelpful_count)->toBe(1)
        ->and($article->helpfulness())->toBe(0);
});

it('reports no score at all when nobody has voted', function () {
    // Null and zero are different answers; merging them would put every new
    // page at the top of the "rated badly" list.
    expect(Article::factory()->create()->helpfulness())->toBeNull();
});

it('counts a view without making the article look edited', function () {
    $article = Article::factory()->create(['reviewed_at' => now()->subDay()]);
    $before = $article->updated_at;

    kb()->countView($article);

    expect($article->fresh()->views_count)->toBe(1)
        ->and($article->fresh()->updated_at->timestamp)->toBe($before->timestamp);
});

it('only counts articles a reader may actually open in the category tally', function () {
    $category = Category::factory()->create();
    Article::factory()->for($category)->create();
    Article::factory()->for($category)->internal()->create();

    expect(kb()->categories()->first()->readable_articles_count)->toBe(1);
});

it('keeps articles when their category is deleted', function () {
    // Smazat kategorii je uklizení polic, ne vyhození knih.
    $category = Category::factory()->create();
    $article = Article::factory()->for($category)->create();

    $category->delete();

    expect($article->fresh())->not->toBeNull()
        ->and($article->fresh()->category_id)->toBeNull();
});

it('gives every heading an id so the contents can link to it', function () {
    // Kotva z HeadingPermalink nese id uvnitř nadpisu, ne na něm – bez
    // doplnění by obsah stránky odkazoval do prázdna a vypadalo by to jako
    // článek bez nadpisů.
    $renderer = app(CommonMarkRenderer::class);

    $html = $renderer->render("## První krok\n\ntext\n\n## Druhý krok\n\ntext");

    expect($html)->toContain('id="prvni-krok"');

    $toc = $renderer->tableOfContents($html);

    expect($toc)->toHaveCount(2)
        ->and($toc[0]['title'])->toBe('První krok')
        ->and($toc[1]['id'])->toBe('druhy-krok');
});

it('does not let two headings of the same name share an anchor', function () {
    $renderer = app(CommonMarkRenderer::class);

    $toc = $renderer->tableOfContents(
        $renderer->render("## Poznámky\n\na\n\n## Poznámky\n\nb")
    );

    expect($toc[0]['id'])->not->toBe($toc[1]['id']);
});

it('does not show an internal category just because one public article sits in it', function () {
    // Jméno a popis interní kategorie prozrazují, na čem se dělá – viditelnost
    // kategorie je strop, ne dekorace.
    $internal = Category::factory()->internal()->create(['name' => 'Interní věci']);
    Article::factory()->for($internal)->create();

    expect(kb()->categories()->pluck('name'))->not->toContain('Interní věci');
});

it('shows internal categories to the team', function () {
    $internal = Category::factory()->internal()->create(['name' => 'Interní věci']);
    Article::factory()->for($internal)->internal()->create();

    $reader = new class extends User {};

    expect(kb()->categories($reader)->pluck('name'))->toContain('Interní věci');
});

it('round-trips blocks between the editing rows and the stored shape', function () {
    // Editace splácne `data` do řádku, úložiště drží kanonický tvar. Kdyby se
    // ta konverze rozešla, přišel by autor o obsah tichým způsobem.
    $editor = new class extends ArticleEditor
    {
        /** @param  array<int, array<string, mixed>>  $rows */
        public static function pack(array $rows): string
        {
            return self::toCanonical($rows);
        }

        /** @return array<int, array<string, mixed>> */
        public static function unpack(string $json): array
        {
            return self::toEditing($json);
        }
    };

    $rows = [
        ['type' => 'heading', 'text' => 'Postup'],
        ['type' => 'steps', 'lines' => "První\nDruhý\n\nTřetí"],
    ];

    $stored = json_decode($editor::pack($rows), true);

    expect($stored[0])->toBe(['type' => 'heading', 'data' => ['text' => 'Postup']])
        // Prázdný řádek není krok.
        ->and($stored[1]['data']['items'])->toBe(['První', 'Druhý', 'Třetí']);

    expect($editor::unpack($editor::pack($rows))[1]['lines'])
        ->toBe("První\nDruhý\nTřetí");
});

it('keeps a block whose type nobody can edit any more', function () {
    // Bloky přežijí kód, který je uměl. Zahodit je při uložení by byla ztráta
    // obsahu za běhu nasazení.
    $editor = new class extends ArticleEditor
    {
        /** @return array<int, array<string, mixed>> */
        public static function unpack(string $json): array
        {
            return self::toEditing($json);
        }

        /** @param  array<int, array<string, mixed>>  $rows */
        public static function pack(array $rows): string
        {
            return self::toCanonical($rows);
        }
    };

    $json = json_encode([['type' => 'zmizely-typ', 'data' => ['text' => 'obsah']]]);

    expect(json_decode($editor::pack($editor::unpack($json)), true))
        ->toBe([['type' => 'zmizely-typ', 'data' => ['text' => 'obsah']]]);
});

it('duplicates a block right below the original', function () {
    $editor = new class extends ArticleEditor
    {
        /** @param  array<int, array<string, mixed>>  $rows */
        public function seed(array $rows): void
        {
            $this->blockData = $rows;
        }

        /** @return array<int, array<string, mixed>> */
        public function rows(): array
        {
            return $this->blockData;
        }
    };

    $editor->seed([
        ['type' => 'heading', 'text' => 'A'],
        ['type' => 'text', 'text' => 'B'],
    ]);

    $editor->duplicateBlock(0);

    // Pod originál, ne na konec – kopie se dělá kvůli tomu, co je vedle.
    expect(array_column($editor->rows(), 'text'))->toBe(['A', 'A', 'B']);
});

it('rewrites rich text through the sanitiser', function () {
    // TipTap generuje HTML v prohlížeči, takže je to vstup od uživatele bez
    // ohledu na to, jak důvěryhodný autor je.
    $html = app(RendererRegistry::class)->render(
        ContentFormat::RichText,
        '<h2>Nadpis</h2><p onclick="alert(1)">text</p><script>alert(2)</script>'
    );

    expect($html)->toContain('id="nadpis"')
        ->not->toContain('onclick')
        ->not->toContain('<script');
});

it('stores an uploaded image and hands back a usable address', function () {
    Storage::fake('public');

    $url = app(ImageLibrary::class)->store(
        UploadedFile::fake()->image('schema.png')
    );

    expect($url)->toContain('/images/')
        ->and(Storage::disk('public')->files('images'))->toHaveCount(1);
});

it('lists the newest uploads first in the gallery', function () {
    Storage::fake('public');

    $library = app(ImageLibrary::class);

    $library->store(UploadedFile::fake()->image('starsi.png'));
    $library->store(UploadedFile::fake()->image('novejsi.png'));

    // Galerie odpovídá na „ten, co jsem před chvílí nahrál" – pořadí je celý
    // její smysl, jméno souboru nikomu nic neříká.
    expect($library->recent())->toHaveCount(2);
});

it('does not offer files that are not images', function () {
    Storage::fake('public');

    Storage::disk('public')->put('images/poznamky.txt', 'nic');

    expect(app(ImageLibrary::class)->recent())->toBeEmpty();
});

it('renders every offered block type', function () {
    // Typ nabídnutý v editoru musí mít co vykreslit – jinak jde vložit blok,
    // který se na stránce neobjeví, a autor to zjistí až po publikování.
    $offered = collect(config('knowledge-base.editors.blocks.types'))->flatten();

    expect($offered)->not->toBeEmpty();

    $missing = $offered->reject(
        fn (string $type) => view()->exists('knowledge-base::blocks.'.$type)
    );

    expect($missing->all())->toBe([]);
});

it('embeds a video only from an allowed host', function () {
    $render = fn (string $url) => app(RendererRegistry::class)->render(
        ContentFormat::Blocks,
        [['type' => 'video', 'data' => ['url' => $url]]]
    );

    expect($render('https://www.youtube.com/watch?v=abc123'))
        ->toContain('youtube.com/embed/abc123');

    // Cizí zdroj se nezahodí mlčky – zůstane odkaz, ale žádný iframe.
    expect($render('https://example.test/video.mp4'))
        ->not->toContain('<iframe')
        ->toContain('example.test');
});

it('builds a table from pipe separated rows', function () {
    $html = app(RendererRegistry::class)->render(
        ContentFormat::Blocks,
        [['type' => 'table', 'data' => ['rows' => "Stav | Znamená\nOpen | čeká se na nás"]]]
    );

    expect($html)->toContain('<th>Stav</th>')
        ->toContain('<td>Open</td>');
});

it('keeps relative links, which is how articles point at each other', function () {
    // Sanitizér je bez `allowRelativeLinks()` zahazoval potichu: text zůstal,
    // odkaz zmizel, a v bázi to nikdo nepozná bez kliknutí.
    $html = app(CommonMarkRenderer::class)
        ->render('Začni u [rozsahu](/napoveda/rozsah-vydani).');

    expect($html)->toContain('href="/napoveda/rozsah-vydani"');
});

it('still refuses a javascript link', function () {
    $html = app(CommonMarkRenderer::class)
        ->render('[klikni](javascript:alert(1))');

    expect($html)->not->toContain('javascript:');
});

it('renders both plain and rich text in a block the same way', function () {
    // Starší bloky (a obsah ze seedu) drží holý text, nové HTML z editoru.
    // Obojí musí vypadat jako odstavec, ne jako slepenec řádků.
    $render = fn (string $text) => app(RendererRegistry::class)->render(
        ContentFormat::Blocks,
        [['type' => 'text', 'data' => ['text' => $text]]]
    );

    expect($render("První řádek\nDruhý"))->toContain('<br')
        ->and($render('<p>Už <strong>naformátované</strong></p>'))->toContain('<strong>');
});

it('sanitises rich text that came from a block', function () {
    $html = app(RendererRegistry::class)->render(
        ContentFormat::Blocks,
        [['type' => 'callout', 'data' => ['text' => '<p onclick="alert(1)">pozor</p>']]]
    );

    expect($html)->toContain('pozor')->not->toContain('onclick');
});

it('names every offered block type in every language', function (string $locale) {
    // Přesně tohle se rozbilo při rozšiřování sady: přepsané pole překladů
    // zahodilo půlku klíčů a v editoru se ukázaly syrové identifikátory.
    // Chybějící překlad nespadne, jen ošklivě vypadá — proto test.
    app()->setLocale($locale);

    $missing = collect(config('knowledge-base.editors.blocks.types'))
        ->flatten()
        ->flatMap(fn (string $type) => [
            'kb.editor.block.'.$type,
            'kb.editor.block_hint.'.$type,
        ])
        ->reject(fn (string $key) => __('knowledge-base::'.$key) !== 'knowledge-base::'.$key);

    expect($missing->all())->toBe([]);
})->with(['cs', 'en']);

it('moves a block into the gap the reader dropped it on', function () {
    $editor = new class extends ArticleEditor
    {
        /** @param  array<int, array<string, mixed>>  $rows */
        public function seed(array $rows): void
        {
            $this->blockData = $rows;
        }

        /** @return array<int, string> */
        public function order(): array
        {
            return array_column($this->blockData, 'text');
        }
    };

    $rows = fn () => [
        ['type' => 'text', 'text' => 'A'],
        ['type' => 'text', 'text' => 'B'],
        ['type' => 'text', 'text' => 'C'],
    ];

    // Dolů: mezera se počítá v poli **před** vyjmutím, takže „za C" je 3.
    $editor->seed($rows());
    $editor->moveBlockTo(0, 3);
    expect($editor->order())->toBe(['B', 'C', 'A']);

    // Nahoru: index mezery se vyjmutím neposouvá.
    $editor->seed($rows());
    $editor->moveBlockTo(2, 0);
    expect($editor->order())->toBe(['C', 'A', 'B']);

    // Puštění na vlastní místo nic nepřeskládá.
    $editor->seed($rows());
    $editor->moveBlockTo(1, 1);
    expect($editor->order())->toBe(['A', 'B', 'C']);
});

it('converts markdown into the block schema', function () {
    $blocks = (new MarkdownToBlocks)->convert(<<<'MD'
        ## Nadpis

        Odstavec s **tučným** a [odkazem](/napoveda/x).

        - první
        - druhý

        1. krok jedna
        2. krok dva

        > citace

        ```php
        echo 'ahoj';
        ```

        | Stav | Znamená |
        |---|---|
        | Open | čeká se |

        ---
        MD);

    $types = array_column($blocks, 'type');

    expect($types)->toBe(['heading', 'text', 'list', 'steps', 'quote', 'code', 'table', 'divider']);

    // Inline formátování odstavce přežije – prozaické bloky drží HTML.
    expect($blocks[1]['data']['text'])->toContain('<strong>')->toContain('href="/napoveda/x"');

    expect($blocks[0]['data'])->toBe(['level' => '2', 'text' => 'Nadpis'])
        ->and($blocks[2]['data']['items'])->toBe(['první', 'druhý'])
        ->and($blocks[3]['data']['items'])->toBe(['krok jedna', 'krok dva'])
        ->and($blocks[5]['data']['language'])->toBe('php')
        ->and($blocks[6]['data']['rows'])->toBe("Stav | Znamená\nOpen | čeká se");
});

it('does not lose a paragraph it has no block for', function () {
    // Uzel bez vlastního bloku skončí jako text, ne v koši. Ztratit odstavec
    // při migraci je horší než mít ho v obecnějším bloku.
    $blocks = (new MarkdownToBlocks)->convert(
        '<figure><figcaption>vlastní HTML</figcaption></figure>'
    );

    expect($blocks)->toHaveCount(1)
        ->and($blocks[0]['type'])->toBe('text')
        ->and($blocks[0]['data']['text'])->toContain('vlastní HTML');
});

it('renders converted markdown to the same words it started with', function () {
    $markdown = "## Postup\n\nNejdřív **tohle**.\n\n1. krok\n2. druhý krok";

    $html = app(RendererRegistry::class)->render(
        ContentFormat::Blocks,
        (new MarkdownToBlocks)->convert($markdown)
    );

    expect($html)->toContain('Postup')
        ->toContain('<strong>tohle</strong>')
        ->toContain('druhý krok')
        ->toContain('<h2 id="postup"');
});

it('keeps a link that lives inside a list item', function () {
    // Položky seznamu nesou inline formátování. Escapovaný výpis z odkazu
    // udělal text a v článku to nikdo nepozná bez kliknutí.
    $html = app(RendererRegistry::class)->render(
        ContentFormat::Blocks,
        (new MarkdownToBlocks)->convert(
            "- [Jak vydat verzi](/napoveda/vydat-novou-verzi)\n- druhá položka"
        )
    );

    expect($html)->toContain('href="/napoveda/vydat-novou-verzi"')
        ->toContain('druhá položka');
});

it('re-renders an article without touching its content or its clock', function () {
    $article = Article::factory()->create([
        'body' => '## Nadpis',
        'body_html' => '<p>zastaralé</p>',
        'format' => ContentFormat::Markdown,
        'reviewed_at' => now()->subYear(),
    ]);

    $touched = $article->updated_at;

    kb()->rerender($article);

    expect($article->fresh()->body_html)->toContain('<h2')
        ->and($article->fresh()->body)->toBe('## Nadpis')
        // Překreslení není úprava: nesmí resetovat hodiny kontroly ani
        // vypadat jako by článek někdo editoval.
        ->and($article->fresh()->updated_at->timestamp)->toBe($touched->timestamp);
});

it('keeps the styles the editor produces and drops the rest', function () {
    // Barvu, podbarvení a velikost TipTap vykresluje inline – bez `style` by
    // z formátování nezbylo nic. Povolit ho celý je ale zbytečně široké.
    $render = fn (string $html) => app(RendererRegistry::class)
        ->render(ContentFormat::RichText, $html);

    expect($render('<p><span style="color: #dc2626">červeně</span></p>'))
        ->toContain('color: #dc2626')
        ->and($render('<p><span style="font-size: 1.25em">větší</span></p>'))
        ->toContain('font-size: 1.25em')
        ->and($render('<p style="text-align: center">na střed</p>'))
        ->toContain('text-align: center');

    // Co editor nevyrábí, se zahodí – včetně věcí, které umí načíst obsah zvenčí.
    $nasty = $render('<p style="background-image: url(https://zlo.test/x.png); position: fixed; color: red">text</p>');

    expect($nasty)->toContain('color: red')
        ->not->toContain('background-image')
        ->not->toContain('position')
        ->not->toContain('zlo.test');
});

it('keeps a table with merged cells and a header row', function () {
    $html = app(RendererRegistry::class)->render(
        ContentFormat::RichText,
        '<table><thead><tr><th colspan="2">Hlavička</th></tr></thead>'
        .'<tbody><tr><td>a</td><td>b</td></tr></tbody></table>'
    );

    expect($html)->toContain('<th colspan="2">')->toContain('<td>a</td>');
});

it('keeps underline, subscript and superscript', function () {
    $html = app(RendererRegistry::class)->render(
        ContentFormat::RichText,
        '<p><u>podtrženo</u> H<sub>2</sub>O a m<sup>2</sup></p>'
    );

    expect($html)->toContain('<u>')->toContain('<sub>')->toContain('<sup>');
});

it('keeps a task list but never lets the reader tick it', function () {
    // Stav zaškrtnutí se neukládá – klikatelné políčko by slibovalo paměť,
    // kterou článek nemá.
    $html = app(RendererRegistry::class)->render(
        ContentFormat::RichText,
        '<ul data-type="taskList">'
        .'<li data-checked="true"><label><input type="checkbox" checked></label><div><p>hotovo</p></div></li>'
        .'<li data-checked="false"><label><input type="checkbox"></label><div><p>čeká</p></div></li>'
        .'</ul>'
    );

    expect($html)->toContain('data-type="taskList"')
        ->toContain('data-checked="true"')
        ->toContain('hotovo')
        // Ani nezaškrtnuté políčko nesmí jít kliknout – proto se počítá,
        // že `disabled` dostala **obě**, ne jen to zaškrtnuté.
        ->and(substr_count($html, '<input'))->toBe(2)
        ->and(substr_count($html, 'disabled="disabled"'))->toBe(2);
});
