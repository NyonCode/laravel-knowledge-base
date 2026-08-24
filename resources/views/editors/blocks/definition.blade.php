<input type="text" wire:model.blur="blockData.{{ $index }}.term" placeholder="{{ __('knowledge-base::kb.editor.term') }}"
    class="mb-2 w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm font-medium dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
@include('knowledge-base::editors.blocks._rich', ['field' => 'text'])
