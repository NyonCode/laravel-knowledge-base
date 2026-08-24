<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Enums;

/**
 * Who may read it.
 *
 * One base holds both the team runbook and the customer help centre, so this
 * is the load-bearing field of the whole package: everything that lists,
 * searches or renders an article asks it first, and the default for a new
 * article is deliberately the closed one — a mistake then hides a page that
 * should be public, instead of publishing one that should not be.
 */
enum Visibility: string
{
    /** The team only. Requires an authenticated reader the audience accepts. */
    case Internal = 'internal';

    /** Anyone, signed in or not. Indexable, linkable, quotable. */
    case Public = 'public';

    public function label(): string
    {
        return __('knowledge-base::kb.visibility.'.$this->value);
    }

    public function icon(): string
    {
        return $this === self::Internal ? 'lock-closed' : 'globe-alt';
    }

    public function color(): string
    {
        return $this === self::Internal ? 'amber' : 'sky';
    }

    public function isPublic(): bool
    {
        return $this === self::Public;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
