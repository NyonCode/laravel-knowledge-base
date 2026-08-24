{{--
    Block composer.

    Structure changes on the server — add, move, remove are Livewire calls, not
    JavaScript that mutates a hidden field. One owner for the array means the
    order on screen and the order in the database cannot disagree, which is the
    failure every home-grown block editor eventually has.
--}}
@php
    $blocks = json_decode($this->{$statePath}, true) ?: [];
    $types = (array) config('knowledge-base.editors.blocks.types', []);
@endphp

<div class="space-y-3">
    @forelse ($blocks as $index => $block)
        <div wire:key="kb-block-{{ $index }}" class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-2 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $block['type'] ?? '?' }}</span>
                <div class="flex items-center gap-1">
                    <button type="button" wire:click="moveBlock({{ $index }}, -1)" class="rounded p-1 text-zinc-400 hover:text-zinc-700" aria-label="Nahoru">↑</button>
                    <button type="button" wire:click="moveBlock({{ $index }}, 1)" class="rounded p-1 text-zinc-400 hover:text-zinc-700" aria-label="Dolů">↓</button>
                    <button type="button" wire:click="removeBlock({{ $index }})" class="rounded p-1 text-zinc-400 hover:text-rose-600" aria-label="Smazat">✕</button>
                </div>
            </div>
            <textarea
                rows="4"
                wire:model.blur="blocks.{{ $index }}.data.text"
                class="w-full rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"
            >{{ $block['data']['text'] ?? '' }}</textarea>
        </div>
    @empty
        <p class="rounded-xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
            {{ __('knowledge-base::kb.admin.empty') }}
        </p>
    @endforelse

    <div class="flex flex-wrap gap-2">
        @foreach ($types as $type)
            <button type="button" wire:click="addBlock('{{ $type }}')" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm text-zinc-700 hover:border-sky-400 dark:border-zinc-700 dark:text-zinc-200">
                + {{ $type }}
            </button>
        @endforeach
    </div>
</div>
