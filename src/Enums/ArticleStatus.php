<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Enums;

/**
 * Where an article is in the editorial pipeline.
 *
 * Separate from {@see Visibility} on purpose, and the pair is the whole point:
 * status is *our* readiness, visibility is *whose* eyes. A finished internal
 * runbook is `Published` + `Internal` — published does not mean public, and
 * conflating the two is how private notes end up on the open web.
 */
enum ArticleStatus: string
{
    /** Being written. Never listed, never searchable, reachable only by its author. */
    case Draft = 'draft';

    /** Written and waiting for someone else to read it. */
    case InReview = 'in_review';

    /** Live for whoever {@see Visibility} allows. */
    case Published = 'published';

    /**
     * Superseded but kept.
     *
     * Deleting is worse: old links keep arriving and an archived page can say
     * "this moved" where a 404 says nothing.
     */
    case Archived = 'archived';

    public function label(): string
    {
        return __('knowledge-base::kb.status.'.$this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::InReview => 'amber',
            self::Published => 'emerald',
            self::Archived => 'zinc',
        };
    }

    /** Is it readable at all by someone who is not editing it? */
    public function isLive(): bool
    {
        return $this === self::Published;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
