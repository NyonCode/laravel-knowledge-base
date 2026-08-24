<textarea rows="5" wire:model.blur="blockData.{{ $index }}.rows" spellcheck="false" placeholder="Stav | Znamená&#10;Open | čeká se na nás"
    class="w-full rounded-lg border border-zinc-200 p-3 font-mono text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
<p class="mt-1 text-xs text-zinc-500">{{ __('knowledge-base::kb.editor.table_hint') }}</p>
