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

    <div class="flex flex-wrap gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-800">
        @foreach ($types as $type)
            <button type="button" wire:click="addBlock('{{ $type }}')"
                class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm text-zinc-700 transition hover:border-sky-400 hover:text-sky-700 dark:border-zinc-700 dark:text-zinc-200">
                + {{ __('knowledge-base::kb.editor.block.'.$type) ?: $type }}
            </button>
        @endforeach
    </div>
</div>
