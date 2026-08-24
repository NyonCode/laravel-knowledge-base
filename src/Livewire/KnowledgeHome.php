<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;
use Livewire\Component;
use NyonCode\KnowledgeBase\Contracts\KnowledgeAudience;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Services\KnowledgeBase;

/**
 * The front door: one search field, then the map.
 *
 * Search comes first because readers type before they browse — they arrive
 * with a sentence in their head, not a place in a tree. The category grid is
 * what they fall back to when the sentence fails them, so it is present and
 * scannable rather than hidden behind the search.
 */
class KnowledgeHome extends Component
{
    /** In the URL so a search can be linked, quoted in a ticket and reloaded. */
    #[Url(as: 'q', except: '')]
    public string $term = '';

    public function render(KnowledgeBase $kb): View
    {
        $reader = auth()->user();
        $searching = trim($this->term) !== '';

        return view('knowledge-base::public.home', [
            'searching' => $searching,
            'results' => $searching ? $kb->find($this->term, $reader) : collect(),
            'categories' => $searching ? collect() : $kb->categories($reader),
            'popular' => $searching ? collect() : $this->popular(),
        ])->title(__('knowledge-base::kb.home.title'));
    }

    /**
     * What everybody else needed.
     *
     * On an empty search this beats "newest": a reader who has not typed
     * anything is usually having one of the five problems everybody has.
     *
     * @return Collection<int, Article>
     */
    protected function popular(): Collection
    {
        $query = Article::query()->with('category');

        app(KnowledgeAudience::class)
            ->scopeVisible($query, auth()->user());

        return $query->orderByDesc('views_count')->limit(6)->get();
    }
}
