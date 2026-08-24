{{-- The unit of every listing. Title first, then the promise of the kind, then
     the excerpt — a reader scanning a column of these decides on the first
     line and should not have to read three to rule a page out. --}}
<a
    href="{{ \NyonCode\KnowledgeBase\Support\Routes::article($article) }}"
    wire:navigate
    class="group flex flex-col gap-1 rounded-xl border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-600 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700"
>
    <span class="flex items-start justify-between gap-3">
        <span class="font-medium text-zinc-900 group-hover:text-sky-700 dark:text-zinc-100 dark:group-hover:text-sky-400">
            {{ $article->title }}
        </span>
        @include('knowledge-base::partials.kind-badge', ['kind' => $article->kind])
    </span>

    @if ($article->excerpt)
        <span class="line-clamp-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $article->excerpt }}</span>
    @endif

    <span class="mt-1 flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-500">
        @if ($article->category)
            <span>{{ $article->category->name }}</span>
        @endif
        <span>{{ __('knowledge-base::kb.article.reading_time', ['minutes' => $article->readingMinutes()]) }}</span>
        @unless ($article->visibility->isPublic())
            <span class="inline-flex items-center gap-1 rounded bg-amber-50 px-1.5 py-0.5 font-medium text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
                {{ $article->visibility->label() }}
            </span>
        @endunless
    </span>
</a>
