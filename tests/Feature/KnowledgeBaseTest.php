<?php

declare(strict_types=1);

use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Models\Category;
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
