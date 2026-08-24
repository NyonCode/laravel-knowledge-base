<div class="flex gap-2">
    <input type="text" wire:model.blur="blockData.{{ $index }}.src" placeholder="https://…"
        class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
    <button type="button" wire:click="openImagePicker({{ $index }})"
        class="shrink-0 rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:border-sky-400 dark:border-zinc-700 dark:text-zinc-200">
        {{ __('knowledge-base::kb.editor.pick_image') }}
    </button>
</div>

@if (! empty($this->blockData[$index]['src']))
    {{-- Náhled hned pod polem: adresa sama o sobě neřekne, jestli je to ten
         správný obrázek, a ve výsledku to zjistíš až po uložení. --}}
    <img src="{{ $this->blockData[$index]['src'] }}" alt=""
         class="mt-2 max-h-40 rounded-lg border border-zinc-200 object-contain dark:border-zinc-800" />
@endif

<div class="mt-2 grid gap-2 sm:grid-cols-2">
    <input type="text" wire:model.blur="blockData.{{ $index }}.alt" placeholder="{{ __('knowledge-base::kb.editor.block.alt') }}"
        class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
    <input type="text" wire:model.blur="blockData.{{ $index }}.caption" placeholder="{{ __('knowledge-base::kb.editor.block.caption') }}"
        class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
</div>
{{-- Popisek alt není ozdoba: obrázek bez něj je pro čtečku i pro vyhledávání
     prázdné místo, a v návodu bývá právě ten obrázek tím podstatným. --}}
