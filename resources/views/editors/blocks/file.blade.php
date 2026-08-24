<div class="grid gap-2 sm:grid-cols-2">
    <input type="text" wire:model.blur="blockData.{{ $index }}.url" placeholder="https://…" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
    <input type="text" wire:model.blur="blockData.{{ $index }}.label" placeholder="{{ __('knowledge-base::kb.editor.block.file') }}" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
</div>
