<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use NyonCode\KnowledgeBase\Enums\ArticleStatus;
use NyonCode\KnowledgeBase\Livewire\Admin\ArticleList;
use NyonCode\KnowledgeBase\Models\Article;

/*
 * Seznam článků. Testuje se hlavně to, co se na obrazovce nedá ověřit
 * pohledem: že počet na čipu a řádky pod ním odpovídají na tutéž otázku, že
 * se poplach nedá ztišit filtrem, a že se z jednoho důvodu dá vrátit na celek.
 */

function listUser(): User
{
    $user = new User;
    $user->forceFill(['id' => 1]);

    return $user;
}

function testList(): Testable
{
    return Livewire::actingAs(listUser())->test(ArticleList::class);
}

it('lands on the whole base, healthy articles included', function () {
    $draft = Article::factory()->draft()->create(['title' => 'Nedopsané']);
    $rated = Article::factory()->create([
        'title' => 'Zhrzené',
        'helpful_count' => 2,
        'unhelpful_count' => 9,
        'views_count' => 40,
    ]);
    $fine = Article::factory()->create(['title' => 'V pořádku']);

    testList()
        ->assertSet('reason', '')
        ->assertSee($draft->title)
        ->assertSee($rated->title)
        ->assertSee($fine->title);
});

it('counts the rot over the whole base even while a filter is on', function () {
    // Poplach se filtrem nesmí ztišit: kdyby se počítal z toho, co je zrovna
    // vidět, po kliknutí na „Koncept" by ostatní důvody spadly na nulu.
    Article::factory()->draft()->create();
    Article::factory()->stale()->create();

    testList()
        ->call('filterByReason', Article::REASON_DRAFT)
        ->assertViewHas('counts', fn (array $counts) => $counts['queue'] === 2
            && $counts['reasons'][Article::REASON_STALE] === 1)
        ->assertViewHas('total', 2);
});

it('searches the base, not the filtered selection', function () {
    // Jinak se zdravý článek nenajde a nikde nestojí proč — to je daň, kterou
    // platila fronta jako výchozí plocha.
    Article::factory()->create(['title' => 'Zdravý a nalezitelný']);

    testList()
        ->set('term', 'nalezitelný')
        ->assertSee('Zdravý a nalezitelný');
});

it('counts each reason as its own chip', function () {
    Article::factory()->draft()->count(2)->create();
    Article::factory()->stale()->create();

    testList()->assertViewHas('counts', fn (array $counts) => $counts['reasons'] === [
        Article::REASON_DRAFT => 2,
        Article::REASON_STALE => 1,
        Article::REASON_UNHELPFUL => 0,
    ] && $counts['queue'] === 3);
});

it('narrows to one reason and widens again on a second click', function () {
    $draft = Article::factory()->draft()->create(['title' => 'Nedopsané']);
    $stale = Article::factory()->stale()->create(['title' => 'Zetlelé']);

    // Druhý klik na týž čip filtr zruší — jinak by se na celek nedalo vrátit
    // jinak než dalším tlačítkem, a to je přesně to, co odsud zmizelo.
    testList()
        ->call('filterByReason', Article::REASON_DRAFT)
        ->assertSet('reason', Article::REASON_DRAFT)
        ->assertSee($draft->title)
        ->assertDontSee($stale->title)
        ->call('filterByReason', Article::REASON_DRAFT)
        ->assertSet('reason', '')
        ->assertSee($stale->title);
});

it('refuses a reason the address bar made up', function () {
    Livewire::actingAs(listUser())
        ->withQueryParams(['reason' => 'nonsense'])
        ->test(ArticleList::class)
        ->assertSet('reason', '');
});

it('still filters by status and category', function () {
    $archived = Article::factory()->create([
        'title' => 'Odložené',
        'status' => ArticleStatus::Archived,
    ]);
    $live = Article::factory()->create(['title' => 'Živé']);

    testList()
        ->set('status', ArticleStatus::Archived->value)
        ->assertSee($archived->title)
        ->assertDontSee($live->title);
});

it('says in the row why the article needs attention', function () {
    $rated = Article::factory()->create([
        'helpful_count' => 2,
        'unhelpful_count' => 9,
        'views_count' => 40,
    ]);

    expect($rated->attentionReasons())->toBe([Article::REASON_UNHELPFUL]);

    // Odznak nese míru, ne jen jméno důvodu: „špatně hodnocené" neřekne,
    // jestli to hoří.
    testList()->assertSee('18 %');
});

it('does not call an unfinished article stale as well as a draft', function () {
    // Koncept nehnije, chybí. Dva odznaky by z jednoho problému udělaly dva.
    $draft = Article::factory()->draft()->create(['reviewed_at' => null]);

    expect($draft->attentionReasons())->toBe([Article::REASON_DRAFT]);
});

it('gives a freshly published article time before calling it stale', function () {
    // Dřív stačilo prázdné `reviewed_at` a stránka byla zastaralá v den
    // vydání — seznam pak tvrdil něco jiného než odznak na článku.
    $fresh = Article::factory()->create(['reviewed_at' => null, 'published_at' => now()]);

    expect($fresh->attentionReasons())->toBe([])
        ->and(Article::query()->stale()->pluck('id'))->not->toContain($fresh->id);
});
