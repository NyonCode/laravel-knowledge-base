<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use NyonCode\KnowledgeBase\Contracts\KnowledgeAudience;
use NyonCode\KnowledgeBase\Enums\ArticleKind;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Models\Category;

/**
 * One collection, grouped by what each page is for.
 *
 * Grouping by {@see ArticleKind} rather than listing alphabetically is the
 * whole reason the kind exists: "Get started" and "Reference" are different
 * errands, and a flat list makes the reader sort them out themselves.
 */
class CategoryPage extends Component
{
    public Category $category;

    public function mount(Category $category, KnowledgeAudience $audience): void
    {
        // A category can be internal even when nothing in it is; check it
        // before its contents, or the page frame leaks what the list hides.
        abort_if(
            ! $category->visibility->isPublic()
                && ! $audience->canSeeInternal(auth()->user()),
            404
        );

        $this->category = $category;
    }

    public function render(KnowledgeAudience $audience): View
    {
        $query = $this->category->articles()->getQuery();
        $audience->scopeVisible($query, auth()->user());

        $articles = $query->ordered()->get();

        return view('knowledge-base::public.category', [
            'groups' => collect(ArticleKind::cases())
                ->sortBy(fn (ArticleKind $kind) => $kind->weight())
                ->mapWithKeys(fn (ArticleKind $kind) => [
                    $kind->value => [
                        'kind' => $kind,
                        'articles' => $articles->filter(
                            fn (Article $a) => $a->kind === $kind
                        )->values(),
                    ],
                ])
                ->filter(fn (array $group) => $group['articles']->isNotEmpty()),
            'total' => $articles->count(),
        ])->title($this->category->name);
    }
}
