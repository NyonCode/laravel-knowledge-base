{{--
    ⌘K from anywhere in the host app.

    Alpine owns opening and focus (instant, no round trip); Livewire owns the
    results. Splitting it that way is what makes the palette feel native —
    a server round trip just to show an empty box is the thing people notice.
--}}
<div
    x-data="{
        open: @entangle('open').live,
        index: 0,
        move(delta, max) { this.index = Math.max(0, Math.min(max, this.index + delta)) },
    }"
    x-on:keydown.window.prevent.cmd.k="open = true"
    x-on:keydown.window.prevent.ctrl.k="open = true"
    x-on:keydown.escape.window="open = false"
>
    <button
        type="button"
        x-on:click="open = true"
        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm text-zinc-500 transition hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900"
    >
        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />
        </svg>
        {{ __('knowledge-base::kb.search.open') }}
        <kbd class="rounded border border-zinc-300 px-1 text-[10px] dark:border-zinc-700">⌘K</kbd>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-start justify-center bg-zinc-900/40 p-4 pt-[12vh] backdrop-blur-sm"
        x-on:click.self="open = false"
        role="dialog"
        aria-modal="true"
    >
        <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900" x-trap.noscroll="open">
            <div class="flex items-center gap-3 border-b border-zinc-200 px-4 dark:border-zinc-800">
                <svg class="h-5 w-5 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z" />
                </svg>
                <input
                    type="search"
                    x-ref="field"
                    x-effect="open && $nextTick(() => $refs.field.focus())"
                    wire:model.live.debounce.250ms="term"
                    x-on:keydown.down.prevent="move(1, {{ max(0, $results->count() - 1) }})"
                    x-on:keydown.up.prevent="move(-1, {{ max(0, $results->count() - 1) }})"
                    placeholder="{{ __('knowledge-base::kb.home.placeholder') }}"
                    class="w-full border-0 bg-transparent py-4 text-base text-zinc-900 placeholder:text-zinc-400 focus:outline-none focus:ring-0 dark:text-white"
                />
            </div>

            <div class="max-h-80 overflow-y-auto p-2">
                @forelse ($results as $i => $article)
                    <a
                        href="{{ \NyonCode\KnowledgeBase\Support\Routes::article($article) }}"
                        wire:navigate
                        x-on:click="open = false"
                        :class="index === {{ $i }} ? 'bg-sky-50 dark:bg-sky-500/10' : ''"
                        class="flex items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800"
                    >
                        <span class="truncate text-zinc-900 dark:text-zinc-100">{{ $article->title }}</span>
                        <span class="shrink-0 text-xs text-zinc-400">{{ $article->category?->name }}</span>
                    </a>
                @empty
                    <p class="px-3 py-6 text-center text-sm text-zinc-500">
                        {{ trim($term) === '' ? __('knowledge-base::kb.search.hint') : __('knowledge-base::kb.search.none', ['term' => $term]) }}
                    </p>
                @endforelse
            </div>
        </div>
    </div>
</div>
