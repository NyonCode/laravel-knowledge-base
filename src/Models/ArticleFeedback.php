<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use NyonCode\KnowledgeBase\Support\Settings;

/**
 * One reader's verdict on one article.
 *
 * @property int $id
 * @property int $article_id
 * @property int|null $user_id
 * @property bool $helpful
 * @property string|null $comment
 * @property string|null $reader_hash
 */
class ArticleFeedback extends Model
{
    protected $guarded = [];

    protected $casts = [
        'helpful' => 'boolean',
    ];

    public function getTable(): string
    {
        return Settings::string('tables.feedback', 'kb_article_feedback');
    }

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * Votes that came with a written reason.
     *
     * The only ones worth reading one by one — a bare "no" tells you the page
     * is wrong, the sentence tells you what to write instead.
     *
     * @param  Builder<self>  $query
     */
    public function scopeWithComment(Builder $query): void
    {
        $query->whereNotNull('comment')->where('comment', '!=', '');
    }
}
