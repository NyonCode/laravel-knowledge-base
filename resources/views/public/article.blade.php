{{--
    The article. Three columns on a wide screen, one on a phone, and the
    reading column is capped at ~70 characters because that is where prose
    stops being work to read.
--}}
<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
    <div class="lg:grid lg:grid-cols-[1fr_18rem] lg:gap-12">

        <article class="min-w-0">
            <nav class="mb-6 flex items-center gap-2 text-sm text-zinc-500" aria-label="Breadcrumb">
                <a href="{{ \NyonCode\KnowledgeBase\Support\Routes::home() }}" wire:navigate class="hover:text-zinc-900 dark:hover:text-zinc-200">
                    {{ __('knowledge-base::kb.home.title') }}
                </a>
                @if ($article->category)
                    <span aria-hidden="true">/</span>
                    <a href="{{ \NyonCode\KnowledgeBase\Support\Routes::category($article->category) }}" wire:navigate class="hover:text-zinc-900 dark:hover:text-zinc-200">
                        {{ $article->category->name }}
                    </a>
                @endif
            </nav>

            <header>
                <div class="flex flex-wrap items-center gap-2">
                    @include('knowledge-base::partials.kind-badge', ['kind' => $article->kind])
                    @unless ($article->visibility->isPublic())
                        <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-500/10 dark:text-amber-300">
                            {{ $article->visibility->label() }}
                        </span>
                    @endunless

                    @if ($editable)
                        <a
                            href="{{ \NyonCode\KnowledgeBase\Support\Routes::adminEdit($article) }}"
                            wire:navigate
                            class="ml-auto inline-flex items-center gap-1 rounded-md border border-zinc-300 px-2 py-0.5 text-xs font-medium text-zinc-600 transition hover:border-zinc-400 hover:text-zinc-900 dark:border-zinc-700 dark:text-zinc-300"
                        >{{ __('knowledge-base::kb.article.edit') }}</a>
                    @endif
                </div>

                <h1 class="mt-3 text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">
                    {{ $article->title }}
                </h1>

                {{-- The kind's promise, spelled out. It costs one line and
                     saves a reader three minutes in the wrong quadrant. --}}
                <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $article->kind->promise() }}</p>

                <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500">
                    <span>{{ __('knowledge-base::kb.article.reading_time', ['minutes' => $article->readingMinutes()]) }}</span>
                    @if ($article->reviewed_at)
                        <span>{{ __('knowledge-base::kb.article.reviewed', ['date' => $article->reviewed_at->isoFormat('D. M. YYYY')]) }}</span>
                    @elseif ($article->updated_at)
                        <span>{{ __('knowledge-base::kb.article.updated', ['date' => $article->updated_at->isoFormat('D. M. YYYY')]) }}</span>
                    @endif
                </div>
            </header>

            {{-- Staleness is admitted to the reader, not hidden from them.
                 A page that says "nobody has checked this lately" is trusted
                 more than one that silently pretends to be current. --}}
            @if ($article->isStale())
                <div class="mt-6 flex items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-500/30 dark:bg-amber-500/10">
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" />
                    </svg>
                    <p class="text-amber-900 dark:text-amber-200">{{ __('knowledge-base::kb.article.stale') }}</p>
                </div>
            @endif

            <div class="kb-prose prose prose-zinc mt-8 max-w-[70ch] dark:prose-invert prose-headings:scroll-mt-24 prose-a:text-sky-700 dark:prose-a:text-sky-400">
                {!! \NyonCode\KnowledgeBase\Support\Routes::retarget($article->body_html) !!}
            </div>

            {{-- The feedback loop. Two buttons, then a box that only appears
                 after a "no" — asking everyone to write an essay gets nothing,
                 asking the disappointed one question gets the backlog. --}}
            @if (config('knowledge-base.feedback.enabled', true))
                <section class="mt-12 rounded-2xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-800 dark:bg-zinc-900/50">
                    @if ($vote === null)
                        <div class="flex flex-wrap items-center gap-4">
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ __('knowledge-base::kb.feedback.question') }}</p>
                            <div class="flex gap-2">
                                <button type="button" wire:click="helpful" class="rounded-lg border border-zinc-300 bg-white px-4 py-1.5 text-sm font-medium text-zinc-700 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                    {{ __('knowledge-base::kb.feedback.yes') }}
                                </button>
                                <button type="button" wire:click="unhelpful" class="rounded-lg border border-zinc-300 bg-white px-4 py-1.5 text-sm font-medium text-zinc-700 transition hover:border-rose-400 hover:text-rose-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
                                    {{ __('knowledge-base::kb.feedback.no') }}
                                </button>
                            </div>
                        </div>
                    @elseif ($askingWhy)
                        <form wire:submit="sendComment" class="space-y-3">
                            <label for="kb-why" class="block font-medium text-zinc-900 dark:text-zinc-100">
                                {{ __('knowledge-base::kb.feedback.why') }}
                            </label>
                            <textarea
                                id="kb-why"
                                rows="3"
                                wire:model="feedbackComment"
                                placeholder="{{ __('knowledge-base::kb.feedback.why_placeholder') }}"
                                class="w-full rounded-lg border border-zinc-300 bg-white p-3 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                            ></textarea>
                            @error('feedbackComment') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                            <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-1.5 text-sm font-medium text-white dark:bg-white dark:text-zinc-900">
                                {{ __('knowledge-base::kb.feedback.send') }}
                            </button>
                        </form>
                    @else
                        <p class="text-zinc-700 dark:text-zinc-300">
                            {{ $vote ? __('knowledge-base::kb.feedback.thanks') : __('knowledge-base::kb.feedback.sent') }}
                        </p>
                    @endif
                </section>
            @endif

            @if ($related->isNotEmpty())
                <section class="mt-10">
                    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-zinc-500">
                        {{ __('knowledge-base::kb.article.related') }}
                    </h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($related as $item)
                            @include('knowledge-base::partials.article-card', ['article' => $item])
                        @endforeach
                    </div>
                </section>
            @endif
        </article>

        {{-- Sticky contents. Hidden below `lg` rather than collapsed into an
             accordion: on a phone the list is longer than the first screen of
             the article it indexes. --}}
        @if (count($toc) > 1)
            <aside class="hidden lg:block">
                <nav class="sticky top-24" aria-label="{{ __('knowledge-base::kb.article.toc') }}">
                    <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                        {{ __('knowledge-base::kb.article.toc') }}
                    </p>
                    <ul class="space-y-1 border-l border-zinc-200 text-sm dark:border-zinc-800">
                        @foreach ($toc as $heading)
                            <li>
                                <a
                                    href="#{{ $heading['id'] }}"
                                    class="-ml-px block border-l border-transparent py-1 text-zinc-600 transition hover:border-sky-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100 {{ $heading['level'] === 3 ? 'pl-7' : 'pl-4' }}"
                                >{{ $heading['title'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
        @endif
    </div>
</div>
