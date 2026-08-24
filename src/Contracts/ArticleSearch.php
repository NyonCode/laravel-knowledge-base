<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Collection;
use NyonCode\KnowledgeBase\Models\Article;

/**
 * The retrieval layer.
 *
 * Search is the front door of a knowledge base — readers type before they
 * browse — so it is a contract from the start: the shipped SQL driver carries
 * a few thousand articles, and past that you bind Scout, Meilisearch or
 * Typesense without touching a view.
 *
 * Implementations must apply {@see KnowledgeAudience::scopeVisible()}. Search
 * that ignores the audience is the most likely way an internal page reaches
 * someone who should not have it: the reader never sees the article, only its
 * title, excerpt and the fact that it exists — which is usually the secret.
 */
interface ArticleSearch
{
    /**
     * @return Collection<int, Article>
     */
    public function search(
        string $term,
        ?Authenticatable $reader = null,
        int $limit = 20
    ): Collection;
}
