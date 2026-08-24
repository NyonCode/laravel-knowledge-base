<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Services;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use NyonCode\KnowledgeBase\Contracts\KnowledgeAudience;
use NyonCode\KnowledgeBase\Enums\ArticleStatus;
use NyonCode\KnowledgeBase\Enums\Visibility;
use NyonCode\KnowledgeBase\Support\Settings;

/**
 * The default audience: public pages for everyone, internal pages behind an
 * ability.
 *
 * Asks the configured gate ability and, when the app never defined it, falls
 * back to "any authenticated user". That fallback is right for a single-team
 * back office and wrong for a customer portal, which is why the contract
 * exists and the config says so out loud.
 */
final class GateKnowledgeAudience implements KnowledgeAudience
{
    public function __construct(private readonly Gate $gate) {}

    public function canSeeInternal(?Authenticatable $reader): bool
    {
        if ($reader === null) {
            return false;
        }

        $ability = Settings::string('audience.internal_ability');

        // `has()` first: `allows()` on an undefined ability answers false, and
        // a fresh install would then hide the whole internal base from the one
        // person who just wrote it.
        return $this->gate->has($ability)
            ? $this->gate->forUser($reader)->allows($ability)
            : true;
    }

    public function scopeVisible(Builder $query, ?Authenticatable $reader): void
    {
        // Unpublished is invisible to *readers* regardless of who they are;
        // editing happens on the admin surfaces, which do not come through here.
        $query->where('status', ArticleStatus::Published->value);

        if (! $this->canSeeInternal($reader)) {
            $query->where('visibility', Visibility::Public->value);
        }
    }

    public function canManage(?Authenticatable $reader): bool
    {
        return $this->canSeeInternal($reader);
    }
}
