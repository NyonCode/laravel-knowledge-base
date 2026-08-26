<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use NyonCode\KnowledgeBase\Contracts\ArticleSearch;
use NyonCode\KnowledgeBase\Contracts\KnowledgeAudience;
use NyonCode\KnowledgeBase\Enums\ArticleStatus;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Models\ArticleFeedback;
use NyonCode\KnowledgeBase\Models\Category;
use NyonCode\KnowledgeBase\Support\Cast;
use NyonCode\KnowledgeBase\Support\ReaderFingerprint;
use NyonCode\KnowledgeBase\Support\Settings;

/**
 * The knowledge base as one object.
 *
 * Components orchestrate; the rules live here. Anything that decides *what a
 * reader may see*, *what a save does to history* or *what a vote counts as*
 * belongs in this class, so that a second surface — an API, a command, a
 * different front end — cannot reach a different answer.
 */
final class KnowledgeBase
{
    public function __construct(
        private readonly KnowledgeAudience $audience,
        private readonly ArticleSearch $search,
        private readonly RendererRegistry $renderers,
    ) {}

    // --- Reading -------------------------------------------------------------

    /**
     * Categories this reader may browse, with how many articles they may read
     * in each.
     *
     * The count is audience-aware on purpose: a category showing "12 articles"
     * that opens onto three is worse than no count at all.
     *
     * @return EloquentCollection<int, Category>
     */
    public function categories(?Authenticatable $reader = null): EloquentCollection
    {
        // Viditelnost kategorie je **strop**, ne dekorace: jméno a popis
        // interní kategorie prozrazují, na čem se dělá, i když je uvnitř
        // náhodou jeden veřejný článek. Ptá se se proto na ni dřív než na
        // její obsah — ten článek zůstane dosažitelný odkazem i hledáním.
        return Category::query()
            ->roots()
            ->unless(
                $this->audience->canSeeInternal($reader),
                fn (Builder $query) => $query->public()
            )
            ->ordered()
            ->with(['children' => static fn (Relation $q) => $q->orderBy('sort_order')])
            ->withCount([
                'articles as readable_articles_count' => fn (Builder $q) => $this
                    ->audience
                    ->scopeVisible($q, $reader),
            ])
            ->get()
            ->filter(fn (Category $category) => $category->readable_articles_count > 0
                || $category->children->isNotEmpty())
            ->values();
    }

    /**
     * One article by slug, or null when the reader may not have it.
     *
     * Null rather than a thrown 403: telling an anonymous visitor that
     * `internal-runbook` exists but is forbidden leaks the thing worth hiding.
     * The caller turns this into a 404.
     */
    public function article(string $slug, ?Authenticatable $reader = null): ?Article
    {
        $query = Article::query()->with(['category', 'author']);

        $this->audience->scopeVisible($query, $reader);

        return $query->where('slug', $slug)->first();
    }

    /**
     * @return Collection<int, Article>
     */
    public function find(string $term, ?Authenticatable $reader = null): Collection
    {
        return $this->search->search(
            $term,
            $reader,
            Settings::int('search.limit', 20)
        );
    }

    /**
     * What else to read — same category first, then the same kind.
     *
     * Deliberately not "most viewed": a reader at the bottom of a page is
     * mid-task, and popularity is a worse guess at their next step than
     * proximity.
     *
     * @return EloquentCollection<int, Article>
     */
    public function related(Article $article, ?Authenticatable $reader = null, int $limit = 4): EloquentCollection
    {
        $query = Article::query()->whereKeyNot($article->getKey());

        $this->audience->scopeVisible($query, $reader);

        return $query
            ->orderByRaw('case when category_id = ? then 0 else 1 end', [$article->category_id])
            ->orderByRaw('case when kind = ? then 0 else 1 end', [$article->kind->value])
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Count a read.
     *
     * A bare increment, no model events, no updated_at: a page view must not
     * make an article look edited, or the freshness clock resets every time
     * somebody reads the page it is meant to be watching.
     */
    public function countView(Article $article): void
    {
        $article->newQuery()
            ->whereKey($article->getKey())
            ->update(['views_count' => $article->views_count + 1]);
    }

    // --- Writing -------------------------------------------------------------

    /**
     * Save an article and keep the version that was there before.
     *
     * The revision is written **before** the new body lands, so history holds
     * what was replaced rather than what replaced it.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function save(Article $article, array $attributes, ?Authenticatable $editor = null, ?string $note = null): Article
    {
        if ($article->exists && $this->changesBody($article, $attributes)) {
            $this->snapshot($article, $editor, $note);
        }

        $article->fill($attributes);

        // Rendered on write, once, with the renderer that matches how this
        // article was written — not with whatever the installation prefers now.
        if ($article->isDirty(['body', 'format'])) {
            $format = $article->format ?? ContentFormat::Markdown;

            $article->body_html = $this->renderers->render(
                $format,
                $format->isStructured()
                    ? self::decodeBlocks((string) $article->body)
                    : (string) $article->body
            );
        }

        if ($editor !== null && blank($article->author_id)) {
            $article->author_id = Cast::int($editor->getAuthIdentifier());
        }

        // Publishing stamps the date once. Re-saving a published article is an
        // edit, not a re-publication, and moving the date would reshuffle every
        // "newest first" list on every typo fix.
        if ($article->status === ArticleStatus::Published && $article->published_at === null) {
            $article->published_at = now();
        }

        // Uložení **je** kontrola. Kdo článek právě přepsal, tím řekl víc než
        // tlačítkem „pořád platí" — a bez tohohle razítka by mu stránka dál
        // visela ve frontě „dlouho neověřené" a nabízela, ať potvrdí, co
        // před vteřinou napsal.
        $article->reviewed_at = now();

        $article->save();

        $this->pruneRevisions($article);

        return $article;
    }

    /**
     * Vykreslí článek znovu z jeho zdroje.
     *
     * `save()` překresluje jen při změně těla, což je správně — jenže když se
     * změní **renderer** (jiný sanitizér, opravený partial, zapnutý
     * zvýrazňovač), uložené HTML zůstane staré a nic na to neupozorní.
     * Tohle je ta cesta, jak to srovnat, aniž by se sahalo na obsah.
     *
     * Zapisuje potichu: překreslení není úprava článku a nemá mu hýbat
     * `updated_at` ani hodinami kontroly.
     */
    public function rerender(Article $article): void
    {
        $format = $article->format ?? ContentFormat::Markdown;

        $article->forceFill([
            'body_html' => $this->renderers->render(
                $format,
                $format->isStructured()
                    ? self::decodeBlocks((string) $article->body)
                    : (string) $article->body
            ),
        ])->saveQuietly();
    }

    /**
     * Překreslí celou bázi. Vrací počet článků.
     */
    public function rerenderAll(): int
    {
        $count = 0;

        Article::query()->chunkById(100, function ($articles) use (&$count) {
            foreach ($articles as $article) {
                $this->rerender($article);
                $count++;
            }
        });

        return $count;
    }

    /** Mark an article as still true today. */
    public function markReviewed(Article $article): void
    {
        $article->forceFill(['reviewed_at' => now()])->save();
    }

    /**
     * Record a reader's verdict.
     *
     * One vote per reader per article, enforced by the unique index rather
     * than by looking first — two tabs race past a check, not past a
     * constraint. A changed mind updates the row and moves the counters.
     */
    public function recordFeedback(
        Article $article,
        bool $helpful,
        ?string $comment = null,
        ?Authenticatable $reader = null,
    ): void {
        $hash = ReaderFingerprint::make($reader);

        $existing = ArticleFeedback::query()
            ->where('article_id', $article->getKey())
            ->where('reader_hash', $hash)
            ->first();

        if ($existing !== null && $existing->helpful === $helpful && blank($comment)) {
            return;
        }

        ArticleFeedback::query()->updateOrCreate(
            ['article_id' => $article->getKey(), 'reader_hash' => $hash],
            [
                'helpful' => $helpful,
                'comment' => $comment,
                'user_id' => $reader?->getAuthIdentifier(),
            ],
        );

        $this->recountFeedback($article);
    }

    /**
     * @return EloquentCollection<int, Article>
     */
    public function stale(): EloquentCollection
    {
        return Article::query()->stale()->orderBy('reviewed_at')->get();
    }

    // --- Internals -----------------------------------------------------------

    /** @return array<int, mixed> */
    private static function decodeBlocks(string $body): array
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) ? array_values($decoded) : [];
    }

    /** @param  array<string, mixed>  $attributes */
    private function changesBody(Article $article, array $attributes): bool
    {
        return array_key_exists('body', $attributes)
            && $attributes['body'] !== $article->body;
    }

    private function snapshot(Article $article, ?Authenticatable $editor, ?string $note): void
    {
        $article->revisions()->create([
            'author_id' => $editor?->getAuthIdentifier(),
            'title' => $article->title,
            'body' => $article->body,
            'note' => $note,
        ]);
    }

    private function pruneRevisions(Article $article): void
    {
        $keep = Settings::nullableInt('authoring.max_revisions');

        if ($keep === null) {
            return;
        }

        $ids = $article->revisions()
            ->orderByDesc('id')
            ->skip((int) $keep)
            ->take(PHP_INT_MAX)
            ->pluck('id');

        if ($ids->isNotEmpty()) {
            $article->revisions()->whereKey($ids)->delete();
        }
    }

    /**
     * Recount from the votes themselves rather than nudging the counters.
     *
     * A changed vote moves two numbers at once and incrementing both by hand
     * drifts the moment anything is deleted; the truth is one cheap aggregate
     * away.
     */
    private function recountFeedback(Article $article): void
    {
        $helpful = $article->feedback()->where('helpful', true)->count();
        $unhelpful = $article->feedback()->where('helpful', false)->count();

        $article->forceFill([
            'helpful_count' => $helpful,
            'unhelpful_count' => $unhelpful,
        ])->saveQuietly();
    }
}
