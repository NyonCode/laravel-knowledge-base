@include('knowledge-base::editors.blocks._rich', ['field' => 'text'])
<input type="text" wire:model.blur="blockData.{{ $index }}.source" placeholder="{{ __('knowledge-base::kb.editor.block.quote') }}"
    class="mt-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
