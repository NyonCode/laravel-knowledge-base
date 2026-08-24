<input type="text" wire:model.blur="blockData.{{ $index }}.src" placeholder="https://…"
    class="mb-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
<div class="grid gap-2 sm:grid-cols-2">
    <input type="text" wire:model.blur="blockData.{{ $index }}.alt" placeholder="{{ __('knowledge-base::kb.editor.block.alt') }}"
        class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
    <input type="text" wire:model.blur="blockData.{{ $index }}.caption" placeholder="{{ __('knowledge-base::kb.editor.block.caption') }}"
        class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
</div>
{{-- Popisek alt není ozdoba: obrázek bez něj je pro čtečku i pro vyhledávání
     prázdné místo, a v návodu bývá právě ten obrázek tím podstatným. --}}
