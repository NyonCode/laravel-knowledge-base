{{-- The maintenance queue, not a table of everything. See ArticleList. --}}
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                {{ __('knowledge-base::kb.home.title') }}
            </h1>
            <p class="mt-1 text-sm text-zinc-500">
                {{ $counts['stale'] }} × {{ __('knowledge-base::kb.admin.stale') }}
                · {{ $counts['draft'] }} × {{ __('knowledge-base::kb.status.draft') }}
            </p>
        </div>
        <a href="{{ route('kb.admin.editor') }}" wire:navigate class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
            {{ __('knowledge-base::kb.admin.new') }}
        </a>
    </div>

    <div class="mt-6 flex flex-wrap gap-2">
        @foreach ([
            'needs-work' => __('knowledge-base::kb.admin.needs_work'),
            'stale' => __('knowledge-base::kb.admin.stale'),
            'unhelpful' => __('knowledge-base::kb.admin.unhelpful'),
            '' => __('knowledge-base::kb.admin.all'),
        ] as $value => $label)
            <button
                type="button"
                wire:click="$set('view', '{{ $value }}')"
                @class([
                    'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                    'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' => $view === $value,
                    'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300' => $view !== $value,
                ])
            >{{ $label }}</button>
        @endforeach

        <input
            type="search"
            wire:model.live.debounce.300ms="term"
            placeholder="{{ __('knowledge-base::kb.search.open') }}…"
            class="ml-auto rounded-lg border border-zinc-300 px-3 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
        />
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-800">
            <thead class="bg-zinc-50 text-left text-xs uppercase tracking-wide text-zinc-500 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2.5 font-medium">{{ __('knowledge-base::kb.home.title') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('knowledge-base::kb.status.published') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('knowledge-base::kb.feedback.question') }}</th>
                    <th class="px-4 py-2.5 font-medium">{{ __('knowledge-base::kb.article.reviewed', ['date' => '']) }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-950">
                @forelse ($articles as $article)
                    <tr wire:key="kb-{{ $article->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-900">
                        <td class="px-4 py-3">
                            <a href="{{ route('kb.admin.editor', $article) }}" wire:navigate class="font-medium text-zinc-900 hover:text-sky-700 dark:text-zinc-100">
                                {{ $article->title }}
                            </a>
                            <div class="mt-0.5 flex items-center gap-2 text-xs text-zinc-500">
                                @include('knowledge-base::partials.kind-badge', ['kind' => $article->kind])
                                <span>{{ $article->category?->name }}</span>
                                <span>{{ $article->visibility->label() }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $article->status->label() }}</td>
                        <td class="px-4 py-3">
                            @if ($article->helpfulness() === null)
                                <span class="text-zinc-400">—</span>
                            @else
                                {{-- Score and sample size together: "0 %" from one
                                     vote is noise, from forty it is the backlog. --}}
                                <span @class([
                                    'font-medium',
                                    'text-rose-600 dark:text-rose-400' => $article->needsAttention(),
                                    'text-zinc-700 dark:text-zinc-300' => ! $article->needsAttention(),
                                ])>{{ $article->helpfulness() }} %</span>
                                <span class="text-xs text-zinc-400">({{ $article->helpful_count + $article->unhelpful_count }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span @class([
                                'text-amber-700 dark:text-amber-400' => $article->isStale(),
                                'text-zinc-500' => ! $article->isStale(),
                            ])>
                                {{ $article->reviewed_at?->isoFormat('D. M. YYYY') ?? '—' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-16 text-center text-zinc-500">
                            {{ __('knowledge-base::kb.admin.empty') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $articles->links() }}</div>
</div>
