<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Enums;

/**
 * What the reader came for — the Diátaxis quadrants.
 *
 * Not decoration. The four kinds answer four different questions and a page
 * that mixes them serves none of them: a tutorial that stops to explain the
 * data model loses the beginner, a reference that tells a story is useless to
 * someone who already knows what they want and needs the exact flag.
 *
 * The kind therefore drives more than a badge — it orders listings (learning
 * before looking-up), picks the editor's template, and tells the reader up
 * front whether this page will walk them through something or just state it.
 *
 * @see https://diataxis.fr
 */
enum ArticleKind: string
{
    /** Learning by doing — a guided first run that is guaranteed to work. */
    case Tutorial = 'tutorial';

    /** A recipe for a goal the reader already has. Steps, no theory. */
    case HowTo = 'how-to';

    /** Dry, complete, lookup-shaped: options, fields, endpoints, limits. */
    case Reference = 'reference';

    /** Why it works this way — context and decisions, read away from the keyboard. */
    case Explanation = 'explanation';

    public function label(): string
    {
        return match ($this) {
            self::Tutorial => __('knowledge-base::kb.kind.tutorial'),
            self::HowTo => __('knowledge-base::kb.kind.how_to'),
            self::Reference => __('knowledge-base::kb.kind.reference'),
            self::Explanation => __('knowledge-base::kb.kind.explanation'),
        };
    }

    /** One line telling the reader what to expect before they commit to reading. */
    public function promise(): string
    {
        return match ($this) {
            self::Tutorial => __('knowledge-base::kb.promise.tutorial'),
            self::HowTo => __('knowledge-base::kb.promise.how_to'),
            self::Reference => __('knowledge-base::kb.promise.reference'),
            self::Explanation => __('knowledge-base::kb.promise.explanation'),
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Tutorial => 'academic-cap',
            self::HowTo => 'wrench-screwdriver',
            self::Reference => 'table-cells',
            self::Explanation => 'light-bulb',
        };
    }

    /**
     * Tailwind accent used by the badge and the card rail.
     *
     * A token, not a hex: the host app restyles the whole base by publishing
     * one view, and hard-coded colours would survive that.
     */
    public function color(): string
    {
        return match ($this) {
            self::Tutorial => 'emerald',
            self::HowTo => 'sky',
            self::Reference => 'violet',
            self::Explanation => 'amber',
        };
    }

    /**
     * Reading order inside a category.
     *
     * Someone who lands on a category is more often learning than auditing,
     * so the quadrants are listed in the order they are usually needed.
     */
    public function weight(): int
    {
        return match ($this) {
            self::Tutorial => 1,
            self::HowTo => 2,
            self::Reference => 3,
            self::Explanation => 4,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $kind) => [$kind->value => $kind->label()])
            ->all();
    }
}
