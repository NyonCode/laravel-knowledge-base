{{-- One collection, grouped by errand. See CategoryPage for why the grouping
     is by kind and not alphabetical. --}}
<div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
    <a href="{{ \NyonCode\KnowledgeBase\Support\Routes::home() }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-200">
        &larr; {{ __('knowledge-base::kb.article.back') }}
    </a>

    <header class="mt-3">
        <h1 class="text-3xl font-semibold tracking-tight text-zinc-900 dark:text-white">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $category->description }}</p>
        @endif
    </header>

    @forelse ($groups as $group)
        <section class="mt-10">
            <div class="mb-3 flex items-baseline gap-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-zinc-500">{{ $group['kind']->label() }}</h2>
                <p class="text-sm text-zinc-400">{{ $group['kind']->promise() }}</p>
            </div>
            <div class="grid gap-3">
                @foreach ($group['articles'] as $article)
                    @include('knowledge-base::partials.article-card', ['article' => $article])
                @endforeach
            </div>
        </section>
    @empty
        <p class="mt-10 text-zinc-500">{{ __('knowledge-base::kb.home.empty') }}</p>
    @endforelse
</div>
