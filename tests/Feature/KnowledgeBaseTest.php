<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Livewire\Admin\ArticleEditor;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Models\Category;
use NyonCode\KnowledgeBase\Services\CommonMarkRenderer;
use NyonCode\KnowledgeBase\Services\KnowledgeBase;
use NyonCode\KnowledgeBase\Services\RendererRegistry;

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
