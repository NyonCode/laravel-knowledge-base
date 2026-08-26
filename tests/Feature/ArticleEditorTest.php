<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use NyonCode\KnowledgeBase\Enums\ArticleStatus;
use NyonCode\KnowledgeBase\Livewire\Admin\ArticleEditor;
use NyonCode\KnowledgeBase\Livewire\Admin\ArticleList;
use NyonCode\KnowledgeBase\Models\Article;

/*
 * Co editor dělá po kliknutí. Uložení, které na obrazovce nic nezmění, si
 * člověk vyloží jako rozbité tlačítko — proto se tady testuje i odpověď,
 * ne jen zápis do databáze.
 */

function editorUser(): User
{
    $user = new User;
    $user->forceFill(['id' => 1]);

    return $user;
}

it('says out loud that the article was saved', function () {
    $article = Article::factory()->create(['title' => 'Původní']);

    Livewire::actingAs(editorUser())
        ->test(ArticleEditor::class, ['article' => $article])
        ->set('title', 'Nový název')
        ->call('save')
        ->assertSet('justSaved', true)
        ->assertSee(__('knowledge-base::kb.admin.saved'));

    expect($article->fresh()->title)->toBe('Nový název');
});

it('does not claim a save that failed validation', function () {
    $article = Article::factory()->create();

    Livewire::actingAs(editorUser())
        ->test(ArticleEditor::class, ['article' => $article])
        ->set('title', '')
        ->call('save')
        ->assertHasErrors('title')
        ->assertSet('justSaved', false);
});

it('sends the author back to the list once the article is saved', function () {
    // Uložit znamená hotovo. U nového článku to navíc srovná adresu: psal se
    // na „nový", kde by obnovení stránky otevřelo prázdný editor.
    Route::get('/admin/knowledge', fn () => '')->name('admin.knowledge');

    Livewire::actingAs(editorUser())
        ->test(ArticleEditor::class, ['article' => null])
        ->set('title', 'Jak vydat verzi')
        ->set('blockData', [['type' => 'text', 'html' => '<p>Text.</p>']])
        ->call('save')
        ->assertRedirect(route('admin.knowledge'));

    // Potvrzení musí ten skok přežít, jinak se člověk vrátí na seznam a neví,
    // jestli uložení prošlo.
    expect(session('knowledge-base.saved'))->toBe('Jak vydat verzi');
});

it('names the saved article above the list it returns to', function () {
    Route::get('/admin/knowledge', fn () => '')->name('admin.knowledge');

    session()->flash('knowledge-base.saved', 'Jak vydat verzi');

    Livewire::actingAs(editorUser())
        ->test(ArticleList::class)
        ->assertSet('saved', 'Jak vydat verzi')
        ->assertSee(__('knowledge-base::kb.admin.saved'))
        ->assertSee('Jak vydat verzi');
});

it('stays in the editor when the host mounts no list', function () {
    // Přesměrování na „#" by editor jen rozbilo; odznak v hlavičce zůstává.
    Livewire::actingAs(editorUser())
        ->test(ArticleEditor::class, ['article' => null])
        ->set('title', 'Jak vydat verzi')
        ->set('blockData', [['type' => 'text', 'html' => '<p>Text.</p>']])
        ->call('save')
        ->assertNoRedirect()
        ->assertSet('justSaved', true);
});

it('keeps an unfinished article a draft whatever the status select says', function () {
    $article = Article::factory()->create(['status' => ArticleStatus::InReview]);

    Livewire::actingAs(editorUser())
        ->test(ArticleEditor::class, ['article' => $article])
        ->set('status', ArticleStatus::Published->value)
        ->call('saveAsDraft')
        ->assertSet('status', ArticleStatus::Draft->value);

    expect($article->fresh()->status)->toBe(ArticleStatus::Draft);
});

it('offers "save as draft" only until the article has gone out', function () {
    $draft = Article::factory()->create(['status' => ArticleStatus::Draft]);
    $published = Article::factory()->create(['status' => ArticleStatus::Published]);

    // Zveřejněný článek by tím tlačítkem šel stáhnout z webu, aniž by to
    // někdo chtěl — proto se u něj nenabízí.
    Livewire::actingAs(editorUser())
        ->test(ArticleEditor::class, ['article' => $draft])
        ->assertSee(__('knowledge-base::kb.admin.save_draft'));

    Livewire::actingAs(editorUser())
        ->test(ArticleEditor::class, ['article' => $published])
        ->assertDontSee(__('knowledge-base::kb.admin.save_draft'));
});

it('offers a way out of the editor without saving', function () {
    Route::get('/admin/knowledge', fn () => '')->name('admin.knowledge');

    Livewire::actingAs(editorUser())
        ->test(ArticleEditor::class, ['article' => null])
        ->assertSee(__('knowledge-base::kb.admin.cancel'))
        ->assertSee(route('admin.knowledge'), escape: false);
});

it('leaves the browser nothing to submit on its own', function () {
    // Editor se načítá dlouho (v každém bloku TipTap). Dokud Livewire
    // `wire:submit` nezachytí, odešle prohlížeč formulář sám: stránka se
    // znovu načte, rozepsané změny jsou pryč a vypadá to, že „Uložit" nic
    // nedělá. Tohle je ta jediná pojistka — formulář tam prostě není.
    $source = (string) file_get_contents(__DIR__.'/../../resources/views/admin/editor.blade.php');

    expect($source)->not->toContain('<form ')
        ->and($source)->not->toContain('</form>')
        ->and($source)->not->toContain('type="submit"')
        ->and($source)->toContain('wire:click="save"');
});

it('counts an edit as a review', function () {
    // Kdo článek přepsal, řekl tím víc než tlačítkem „pořád platí" — jinak
    // stránka zůstane ve frontě „dlouho neověřené" hned po přepsání.
    $article = Article::factory()->stale()->create();

    expect($article->isStale())->toBeTrue();

    Livewire::actingAs(editorUser())
        ->test(ArticleEditor::class, ['article' => $article])
        ->set('title', 'Přepsáno dnes')
        ->call('save');

    expect($article->fresh()->isStale())->toBeFalse()
        ->and(Article::query()->stale()->count())->toBe(0);
});

it('offers "still true" only while something is due for review', function () {
    $stale = Article::factory()->stale()->create();
    $fresh = Article::factory()->create();

    Livewire::actingAs(editorUser())
        ->test(ArticleEditor::class, ['article' => $stale])
        ->assertSee(__('knowledge-base::kb.admin.mark_reviewed'));

    // Čerstvě zkontrolovaný článek nemá co potvrzovat; tlačítko, které je
    // vidět pořád, přestane cokoli znamenat.
    Livewire::actingAs(editorUser())
        ->test(ArticleEditor::class, ['article' => $fresh])
        ->assertDontSee(__('knowledge-base::kb.admin.mark_reviewed'));
});
