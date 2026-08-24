{{--
    The landing page is a search field with a map underneath it.

    Deliberately not a wall of categories: readers arrive with a sentence, not
    a place in a tree. The field is autofocused, wide, and the only thing above
    the fold; the grid is what catches the ones whose sentence failed.
--}}
<div class="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">

    <header class="text-center">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">
            {{ __('knowledge-base::kb.home.title') }}
        </h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">
            {{ __('knowledge-base::kb.home.lead') }}
        </p>
    </header>

    <div class="mt-8">
        <label for="kb-search" class="sr-only">{{ __('knowledge-base::kb.search.open') }}</label>
        <div class="relative">
            <svg class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />
            </svg>
            <input
                id="kb-search"
                type="search"
                autofocus
                wire:model.live.debounce.250ms="term"
                placeholder="{{ __('knowledge-base::kb.home.placeholder') }}"
                class="w-full rounded-2xl border border-zinc-200 bg-white py-4 pl-12 pr-4 text-base text-zinc-900 shadow-sm transition placeholder:text-zinc-400 focus:border-sky-500 focus:outline-none focus:ring-4 focus:ring-sky-500/10 dark:border-zinc-800 dark:bg-zinc-900 dark:text-white"
            />
            {{-- The spinner sits inside the field: a result list that swaps
                 under the cursor with no warning reads as a glitch. --}}
            <span wire:loading wire:target="term" class="absolute right-4 top-1/2 -translate-y-1/2">
                <svg class="h-5 w-5 animate-spin text-zinc-400" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                </svg>
            </span>
        </div>
    </div>

    @if ($searching)
        <section class="mt-8" aria-live="polite">
            @if ($results->isEmpty())
                <div class="rounded-xl border border-dashed border-zinc-300 p-10 text-center dark:border-zinc-700">
                    <p class="font-medium text-zinc-900 dark:text-zinc-100">
                        {{ __('knowledge-base::kb.search.none', ['term' => $term]) }}
                    </p>
                    <p class="mt-1 text-sm text-zinc-500">{{ __('knowledge-base::kb.search.none_hint') }}</p>
                </div>
            @else
                <p class="mb-3 text-sm text-zinc-500">
                    {{ trans_choice('knowledge-base::kb.search.results', $results->count(), ['count' => $results->count()]) }}
                </p>
                <div class="grid gap-3">
                    @foreach ($results as $article)
                        @include('knowledge-base::partials.article-card', ['article' => $article])
                    @endforeach
                </div>
            @endif
        </section>
    @else
        <section class="mt-10">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @forelse ($categories as $category)
                    <a
                        href="{{ route(config('knowledge-base.routes.name').'category', $category) }}"
                        wire:navigate
                        class="group rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-sky-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-sky-700"
                    >
                        <span class="flex items-center justify-between">
                            <span class="font-semibold text-zinc-900 group-hover:text-sky-700 dark:text-zinc-100 dark:group-hover:text-sky-400">
                                {{ $category->name }}
                            </span>
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                {{ $category->readable_articles_count }}
                            </span>
                        </span>
                        @if ($category->description)
                            <span class="mt-1 block text-sm text-zinc-600 dark:text-zinc-400">{{ $category->description }}</span>
                        @endif
                    </a>
                @empty
                    <p class="text-zinc-500">{{ __('knowledge-base::kb.home.empty') }}</p>
                @endforelse
            </div>

            @if ($popular->isNotEmpty())
                <h2 class="mb-3 mt-10 text-sm font-semibold uppercase tracking-wide text-zinc-500">
                    {{ __('knowledge-base::kb.home.popular') }}
                </h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($popular as $article)
                        @include('knowledge-base::partials.article-card', ['article' => $article])
                    @endforeach
                </div>
            @endif
        </section>
    @endif
</div>
