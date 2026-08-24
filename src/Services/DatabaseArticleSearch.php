<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use NyonCode\KnowledgeBase\Contracts\ArticleSearch;
use NyonCode\KnowledgeBase\Contracts\KnowledgeAudience;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Support\Settings;

/**
 * Search without a search server.
 *
 * Portable SQL, because a knowledge base holds thousands of articles, not
 * millions, and asking someone to run Meilisearch to find twelve pages is a
 * bad trade. Past that, bind {@see ArticleSearch} to Scout and nothing else
 * in the package changes.
 *
 * Two decisions carry it. **Every word must match somewhere** (AND across
 * terms, OR across columns), so "release plan" does not return every page
 * mentioning a release — that is the difference between a search box and a
 * word cloud. And matches are **weighted by where they land**: a term in the
 * title almost always means the page is about it, the same term in the body
 * usually means it was mentioned in passing.
 */
final class DatabaseArticleSearch implements ArticleSearch
{
    public function __construct(private readonly KnowledgeAudience $audience) {}

    /**
     * @return Collection<int, Article>
     */
    public function search(
        string $term,
        ?Authenticatable $reader = null,
        int $limit = 20
    ): Collection {
        $terms = $this->terms($term);

        if ($terms === []) {
            /** @var EloquentCollection<int, Article> $empty */
            $empty = new EloquentCollection;

            return $empty;
        }

        $query = Article::query()->with('category');

        $this->audience->scopeVisible($query, $reader);

        foreach ($terms as $word) {
            $like = '%'.$this->escape($word).'%';

            $query->where(function (Builder $inner) use ($like) {
                $inner
                    ->where('title', 'like', $like)
                    ->orWhere('excerpt', 'like', $like)
                    ->orWhere('body', 'like', $like);
            });
        }

        [$relevance, $bindings] = $this->relevance($terms);

        /** @var EloquentCollection<int, Article> $results */
        $results = $query
            ->orderByRaw($relevance.' desc', $bindings)
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();

        return $results;
    }

    /**
     * The words a reader typed, quoted phrases kept whole.
     *
     * @return array<int, string>
     */
    private function terms(string $term): array
    {
        $min = Settings::int('search.min_length', 2);

        preg_match_all('/"([^"]+)"|(\S+)/u', trim($term), $matches, PREG_SET_ORDER);

        return collect($matches)
            ->map(static fn (array $m): string => trim(($m[1] ?? '') !== '' ? $m[1] : ($m[2] ?? '')))
            ->filter(static fn (string $word): bool => Str::length($word) >= $min)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Relevance as one SQL expression: the summed weight of every place each
     * term hit.
     *
     * Returned with its bindings rather than interpolated — the term is
     * whatever a visitor typed into a public box, and an ORDER BY is as
     * injectable as a WHERE.
     *
     * @param  array<int, string>  $terms
     * @return array{0: literal-string, 1: array<int, scalar>}
     */
    private function relevance(array $terms): array
    {
        /** @var array<string, int> $weights */
        $weights = Settings::weights('search.weights');

        /** @var list<literal-string> $parts */
        $parts = [];
        /** @var list<scalar> $bindings */
        $bindings = [];

        foreach ($terms as $word) {
            foreach ($weights as $column => $weight) {
                // Column names come from config, never from the request, so
                // they are quoted as identifiers and not bound.
                $parts[] = '(case when '.$this->column($column).' like ? then ? else 0 end)';
                $bindings[] = '%'.$this->escape($word).'%';
                $bindings[] = (int) $weight;
            }
        }

        return [implode(' + ', $parts), $bindings];
    }

    /**
     * Whitelist of orderable columns — config is trusted, but not blindly.
     *
     * Returns a literal so the assembled ORDER BY stays a literal string: the
     * only values that ever reach the SQL are these three, everything the
     * visitor typed goes through a binding.
     *
     * @return literal-string
     */
    private function column(string $name): string
    {
        return in_array($name, ['title', 'excerpt', 'body'], true)
            ? $name
            : 'title';
    }

    /**
     * A `%` or `_` the visitor typed is a character, not a wildcard.
     */
    private function escape(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }
}
