<input type="text" wire:model.blur="blockData.{{ $index }}.url" placeholder="https://www.youtube.com/watch?v=…" class="w-full rounded-lg border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
<p class="mt-1 text-xs text-zinc-500">{{ implode(', ', \NyonCode\KnowledgeBase\Support\Settings::strings('editors.blocks.embed_hosts')) }}</p>
