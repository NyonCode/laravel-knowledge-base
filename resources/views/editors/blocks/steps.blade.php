{{-- Kroky jako řádky, ne jedno pole: číslování patří renderu, ne autorovi. --}}
<textarea rows="5" wire:model.blur="blockData.{{ $index }}.lines" placeholder="{{ __('knowledge-base::kb.editor.block.steps') }}"
    class="w-full rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
