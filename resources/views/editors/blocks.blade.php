{{--
    Blokový editor.

    Strukturu (přidat, přesunout, smazat) mění **server**, ne JavaScript nad
    skrytým polem: jeden vlastník pole je to, co drží pořadí na obrazovce a
    pořadí v databázi pohromadě.

    Každý typ má svoje pole. Jedna textarea pro všechno by z bloků udělala jen
    horší markdown — a přesně proto, aby callout vypadal vždycky jako callout,
    se tenhle editor volí.
--}}
@php
    $types = (array) config('knowledge-base.editors.blocks.types', []);
@endphp

<div class="space-y-3" data-testid="kb-editor-blocks">
    @forelse ($this->blockData as $index => $block)
        @php $type = $block['type'] ?? 'text'; @endphp

        <div wire:key="kb-block-{{ $index }}-{{ $type }}"
             class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-2 flex items-center justify-between">
                <span class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    {{ __('knowledge-base::kb.editor.block.'.$type) ?: $type }}
                </span>
                <div class="flex items-center gap-1">
                    <button type="button" wire:click="moveBlock({{ $index }}, -1)" @disabled($index === 0)
                        class="rounded p-1 text-zinc-400 transition hover:text-zinc-700 disabled:opacity-30 dark:hover:text-zinc-200"
                        aria-label="{{ __('knowledge-base::kb.editor.move_up') }}">&uarr;</button>
                    <button type="button" wire:click="moveBlock({{ $index }}, 1)" @disabled($index === count($this->blockData) - 1)
                        class="rounded p-1 text-zinc-400 transition hover:text-zinc-700 disabled:opacity-30 dark:hover:text-zinc-200"
                        aria-label="{{ __('knowledge-base::kb.editor.move_down') }}">&darr;</button>
                    <button type="button" wire:click="duplicateBlock({{ $index }})"
                        class="rounded p-1 text-zinc-400 transition hover:text-zinc-700 dark:hover:text-zinc-200"
                        aria-label="{{ __('knowledge-base::kb.editor.duplicate') }}">&#10697;</button>
                    <button type="button" wire:click="removeBlock({{ $index }})"
                        class="rounded p-1 text-zinc-400 transition hover:text-rose-600"
                        aria-label="{{ __('knowledge-base::kb.admin.delete') }}">&times;</button>
                </div>
            </div>

            @php $fields = 'knowledge-base::editors.blocks.'.$type; @endphp
            @if (view()->exists($fields))
                @include($fields, ['index' => $index])
            @else
                {{-- Neznámý typ se needituje, ale ani nezmizí: blok přežije kód,
                     který ho uměl, a smazat ho má člověk, ne nasazení. --}}
                <p class="rounded-lg bg-zinc-50 p-3 text-sm text-zinc-500 dark:bg-zinc-950">
                    {{ __('knowledge-base::kb.editor.block.unknown', ['type' => $type]) }}
                </p>
            @endif
        </div>
    @empty
        <p class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
            {{ __('knowledge-base::kb.editor.block.empty') }}
        </p>
    @endforelse

    <button
        type="button"
        wire:click="$set('blockPicker', true)"
        class="w-full rounded-xl border-2 border-dashed border-zinc-300 py-3 text-sm font-medium text-zinc-500 transition hover:border-sky-400 hover:text-sky-600 dark:border-zinc-700"
    >+ {{ __('knowledge-base::kb.editor.add_block') }}</button>

    @if ($blockPicker)
        <div
            class="fixed inset-0 z-50 flex items-start justify-center bg-zinc-900/40 p-4 pt-[10vh] backdrop-blur-sm"
            x-on:click.self="$wire.set('blockPicker', false)"
            x-on:keydown.escape.window="$wire.set('blockPicker', false)"
            role="dialog"
            aria-modal="true"
            data-testid="kb-block-picker"
        >
            <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-zinc-900"
                 x-data="{ q: '' }">
                <div class="border-b border-zinc-200 px-5 py-3 dark:border-zinc-800">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('knowledge-base::kb.editor.pick_block') }}</h2>
                    <input type="search" x-model="q" x-init="$nextTick(() => $el.focus())"
                        placeholder="{{ __('knowledge-base::kb.editor.block_search') }}"
                        class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-1.5 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
                </div>

                <div class="max-h-[60vh] overflow-y-auto p-5">
                    @foreach ($this->blockTypes() as $group => $groupTypes)
                        {{-- Filtruje Alpine, ne server: hledání v sedmnácti
                             položkách nemá cenu posílat na druhou stranu drátu. --}}
                        <div x-show="{{ collect($groupTypes)->map(fn ($t) => "'".e(mb_strtolower(__('knowledge-base::kb.editor.block.'.$t)))."'.includes(q.toLowerCase())")->implode(' || ') }} || q === ''">
                            <h3 class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wide text-zinc-500 first:mt-0">
                                {{ __('knowledge-base::kb.editor.block_group.'.$group) }}
                            </h3>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach ($groupTypes as $type)
                                    <button
                                        type="button"
                                        wire:click="addBlock('{{ $type }}')"
                                        x-show="q === '' || '{{ e(mb_strtolower(__('knowledge-base::kb.editor.block.'.$type))) }}'.includes(q.toLowerCase())"
                                        class="rounded-xl border border-zinc-200 p-3 text-left transition hover:border-sky-400 hover:bg-sky-50 dark:border-zinc-800 dark:hover:bg-sky-500/10"
                                    >
                                        <span class="block text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ __('knowledge-base::kb.editor.block.'.$type) }}
                                        </span>
                                        <span class="block text-xs text-zinc-500">
                                            {{ __('knowledge-base::kb.editor.block_hint.'.$type) }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
