{{-- Markdown: a textarea. No toolbar on purpose — every button is a thing to
     learn, and the people who choose this driver already know the syntax. --}}
<textarea
    wire:model="{{ $statePath }}"
    rows="24"
    spellcheck="true"
    class="w-full rounded-xl border border-zinc-200 bg-white p-4 font-mono text-sm leading-relaxed text-zinc-900 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
></textarea>
