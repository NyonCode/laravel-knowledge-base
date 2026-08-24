<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Support;

use Illuminate\Contracts\View\View;

/**
 * Which layout the package's full-page components render into.
 *
 * The package cannot know: one app calls it `layouts.app`, the next
 * `components.layouts.marketing`, and the admin surfaces almost always want a
 * different one from the reading surfaces. Left unset, Livewire's own default
 * applies — which is right for a fresh Livewire app and wrong for every app
 * that already had layouts before it installed this.
 */
final class Layouts
{
    public static function public(View $view): View
    {
        return self::apply($view, 'public');
    }

    public static function admin(View $view): View
    {
        return self::apply($view, 'admin');
    }

    private static function apply(View $view, string $surface): View
    {
        $layout = config("knowledge-base.layouts.{$surface}");

        return blank($layout) ? $view : $view->layout((string) $layout);
    }
}
