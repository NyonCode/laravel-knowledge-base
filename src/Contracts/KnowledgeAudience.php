<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who is reading, and what they are allowed to see.
 *
 * The package deliberately does not decide this. "Internal" means the team in
 * one app, paying customers in another and a single admin in a third, and a
 * package that guesses gets it wrong in the direction that leaks.
 *
 * Two methods rather than one, because both are needed and only the second is
 * safe: {@see canSeeInternal()} styles the UI, {@see scopeVisible()} is what
 * actually keeps rows out of the answer. Never gate a listing on the boolean
 * alone — hiding a link is not the same as not returning the row.
 */
interface KnowledgeAudience
{
    /** May this reader open internal articles at all? */
    public function canSeeInternal(?Authenticatable $reader): bool;

    /**
     * Narrow a query to what this reader may see.
     *
     * Applied to every listing, every search and every direct lookup, so an
     * article the reader may not read is *absent*, not merely unlinked.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     */
    public function scopeVisible(Builder $query, ?Authenticatable $reader): void;

    /** May this reader write? Drives the admin surfaces, never the reading ones. */
    public function canManage(?Authenticatable $reader): bool;
}
