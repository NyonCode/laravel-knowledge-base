<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Livewire\Admin;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use NyonCode\KnowledgeBase\Contracts\KnowledgeAudience;
use NyonCode\KnowledgeBase\Enums\ArticleStatus;
use NyonCode\KnowledgeBase\Models\Article;
use NyonCode\KnowledgeBase\Models\Category;

/**
 * The editor's desk.
 *
 * Its default view is not "all articles" but **what needs work**: stale pages
 * and pages readers rated badly. A knowledge base fails by rotting, not by
 * being empty, so the maintenance queue is the landing state and the full
 * list is one click away.
 */
class ArticleList extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $term = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $category = '';

    /** `needs-work` (default), `stale`, `unhelpful`, or empty for everything. */
    #[Url(except: 'needs-work')]
    public string $view = 'needs-work';

    public function mount(KnowledgeAudience $audience): void
    {
        abort_unless($audience->canManage(auth()->user()), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['term', 'status', 'category', 'view'], true)) {
            $this->resetPage();
        }
    }

    public function render(): View
    {
        return view('knowledge-base::admin.articles', [
            'articles' => $this->query()->paginate(20),
            'categories' => Category::query()->ordered()->pluck('name', 'id'),
            'statuses' => ArticleStatus::options(),
            'counts' => [
                'stale' => Article::query()->stale()->count(),
                'draft' => Article::query()->where('status', ArticleStatus::Draft->value)->count(),
            ],
        ]);
    }

    /** @return Builder<Article> */
    protected function query(): Builder
    {
        return Article::query()
            ->with('category')
            ->when($this->term !== '', fn (Builder $q) => $q->where(
                fn (Builder $inner) => $inner
                    ->where('title', 'like', '%'.$this->term.'%')
                    ->orWhere('slug', 'like', '%'.$this->term.'%')
            ))
            ->when($this->status !== '', fn (Builder $q) => $q->where('status', $this->status))
            ->when($this->category !== '', fn (Builder $q) => $q->where('category_id', $this->category))
            ->when($this->view === 'stale', fn (Builder $q) => $q->stale())
            ->when($this->view === 'unhelpful', fn (Builder $q) => $q
                ->whereRaw('unhelpful_count > helpful_count')
                ->where('views_count', '>=', 20))
            // The default: everything a maintainer would want to see first,
            // in one query rather than three tabs nobody opens.
            ->when($this->view === 'needs-work', fn (Builder $q) => $q->where(
                fn (Builder $inner) => $inner
                    ->where('status', ArticleStatus::Draft->value)
                    ->orWhere(fn (Builder $stale) => $stale->stale())
                    ->orWhereRaw('unhelpful_count > helpful_count and views_count >= 20')
            ))
            ->orderByDesc('updated_at');
    }
}
