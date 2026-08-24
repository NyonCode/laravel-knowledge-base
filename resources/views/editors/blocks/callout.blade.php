<div class="mb-2 flex gap-2">
    <select wire:model.blur="blockData.{{ $index }}.tone" class="w-36 rounded-lg border border-zinc-200 px-2 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100">
        @foreach (['info', 'success', 'warning', 'danger'] as $tone)
            <option value="{{ $tone }}">{{ __('knowledge-base::kb.editor.tone_'.$tone) }}</option>
        @endforeach
    </select>
    <input type="text" wire:model.blur="blockData.{{ $index }}.title" placeholder="{{ __('knowledge-base::kb.editor.block.callout_title') }}"
        class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
</div>
@include('knowledge-base::editors.blocks._rich', ['field' => 'text'])
{{-- Tón není barva pro barvu: „poznámka" a „tohle ti smaže data" nesmí
     vypadat stejně, jinak si čtenář odvykne oboje číst. --}}
