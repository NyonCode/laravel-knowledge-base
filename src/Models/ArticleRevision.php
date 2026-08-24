<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A saved version of an article's markdown.
 *
 * @property int $article_id
 * @property string $title
 * @property string|null $body
 */
class ArticleRevision extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('knowledge-base.tables.revisions', 'kb_article_revisions');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function author(): BelongsTo
    {
        /** @var class-string<Model> $model */
        $model = config('knowledge-base.models.user')
            ?? config('auth.providers.users.model', 'App\\Models\\User');

        return $this->belongsTo($model, 'author_id');
    }
}
