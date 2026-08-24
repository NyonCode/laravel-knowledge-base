<div class="flex gap-2">
    <select wire:model.blur="blockData.{{ $index }}.level" class="w-24 rounded-lg border border-zinc-200 px-2 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100">
        <option value="2">H2</option>
        <option value="3">H3</option>
    </select>
    <input type="text" wire:model.blur="blockData.{{ $index }}.text" placeholder="{{ __('knowledge-base::kb.editor.block.heading') }}"
        class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-base font-semibold dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
</div>
{{-- Jen H2 a H3: hlouběji už obsah stránky nezobrazuje a nadpis, na který se
     nedá odkázat, je jen tučný text. --}}
