<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use NyonCode\KnowledgeBase\Support\Settings;

/**
 * A saved version of an article's markdown.
 *
 * @property int $id
 * @property int $article_id
 * @property int|null $author_id
 * @property string $title
 * @property string|null $body
 * @property string|null $note
 * @property Carbon|null $created_at
 */
class ArticleRevision extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return Settings::string('tables.revisions', 'kb_article_revisions');
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /** @return BelongsTo<Model, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Settings::userModel(), 'author_id');
    }
}
