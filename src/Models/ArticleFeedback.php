<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One reader's verdict on one article.
 *
 * @property bool $helpful
 * @property string|null $comment
 */
class ArticleFeedback extends Model
{
    protected $guarded = [];

    protected $casts = [
        'helpful' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('knowledge-base.tables.feedback', 'kb_article_feedback');
    }

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
