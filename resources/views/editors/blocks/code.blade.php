<input type="text" wire:model.blur="blockData.{{ $index }}.language" placeholder="{{ __('knowledge-base::kb.editor.block.language') }}"
    class="mb-2 w-40 rounded-lg border border-zinc-200 px-3 py-1.5 font-mono text-xs dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
<textarea rows="6" wire:model.blur="blockData.{{ $index }}.text" spellcheck="false"
    class="w-full rounded-lg border border-zinc-200 p-3 font-mono text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
