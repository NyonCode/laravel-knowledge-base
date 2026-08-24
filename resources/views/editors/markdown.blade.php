{{--
    Markdown: textarea s panelem a zkratkami.

    Panel obaluje výběr, nevkládá WYSIWYG — pořád se píše markdown a v databázi
    je markdown. Jde jen o to, aby se `**`, `##` a odkazy nemusely ťukat ručně
    a aby se v delším textu dalo orientovat.

    Bez jediné nové závislosti: obalení výběru je patnáct řádků Alpine nad
    `selectionStart`/`selectionEnd`. Editor s vlastním bundlem by za to nestál
    a balíček by přinutil hostitele něco kompilovat.
--}}
<div
    data-testid="kb-editor-markdown"
    x-data="{
        el: null,

        init() {
            this.el = this.$refs.area
        },

        /** Obalí výběr (nebo vloží na kurzor) a vrátí kurzor dovnitř. */
        wrap(before, after = null) {
            after = after ?? before

            const el = this.el
            const start = el.selectionStart
            const end = el.selectionEnd
            const selected = el.value.slice(start, end)

            el.setRangeText(before + selected + after, start, end, 'end')

            // Bez výběru dává smysl skončit mezi značkami – tam se píše dál.
            if (start === end) {
                const pos = start + before.length
                el.setSelectionRange(pos, pos)
            }

            this.commit()
        },

        /** Předřadí značku před každý dotčený řádek (nadpis, seznam, citace). */
        prefix(mark) {
            const el = this.el
            const start = el.value.lastIndexOf('\n', el.selectionStart - 1) + 1
            const end = el.selectionEnd
            const block = el.value.slice(start, end)

            const next = block
                .split('\n')
                .map((line) => (line.startsWith(mark) ? line.slice(mark.length) : mark + line))
                .join('\n')

            el.setRangeText(next, start, end, 'end')
            this.commit()
        },

        /** Livewire se o změně přes setRangeText sám nedozví. */
        commit() {
            this.el.dispatchEvent(new Event('input', { bubbles: true }))
            this.el.focus()
        },
    }"
    x-on:keydown.ctrl.b.prevent="wrap('**')"
    x-on:keydown.meta.b.prevent="wrap('**')"
    x-on:keydown.ctrl.i.prevent="wrap('_')"
    x-on:keydown.meta.i.prevent="wrap('_')"
    x-on:keydown.ctrl.k.prevent="wrap('[', '](url)')"
    x-on:keydown.meta.k.prevent="wrap('[', '](url)')"
>
    <div class="flex flex-wrap items-center gap-1 rounded-t-xl border border-b-0 border-zinc-200 bg-zinc-50 px-2 py-1.5 dark:border-zinc-800 dark:bg-zinc-900">
        @foreach ([
            ['label' => 'H2', 'action' => "prefix('## ')", 'title' => 'Nadpis'],
            ['label' => 'H3', 'action' => "prefix('### ')", 'title' => 'Podnadpis'],
        ] as $button)
            <button type="button" x-on:click="{{ $button['action'] }}" title="{{ $button['title'] }}"
                class="rounded px-2 py-1 text-xs font-semibold text-zinc-600 transition hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-800">{{ $button['label'] }}</button>
        @endforeach

        <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>

        <button type="button" x-on:click="wrap('**')" title="Tučně (⌘B)" class="rounded px-2 py-1 text-xs font-bold text-zinc-600 transition hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-800">B</button>
        <button type="button" x-on:click="wrap('_')" title="Kurzíva (⌘I)" class="rounded px-2 py-1 text-xs italic text-zinc-600 transition hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-800">I</button>
        <button type="button" x-on:click="wrap('`')" title="Kód" class="rounded px-2 py-1 font-mono text-xs text-zinc-600 transition hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-800">&lt;/&gt;</button>

        <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>

        <button type="button" x-on:click="prefix('- ')" title="Odrážky" class="rounded px-2 py-1 text-xs text-zinc-600 transition hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-800">&bull;&nbsp;seznam</button>
        <button type="button" x-on:click="prefix('&gt; ')" title="Citace" class="rounded px-2 py-1 text-xs text-zinc-600 transition hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-800">&ldquo;</button>
        <button type="button" x-on:click="wrap('[', '](url)')" title="Odkaz (⌘K)" class="rounded px-2 py-1 text-xs text-zinc-600 transition hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-800">{{ __('knowledge-base::kb.editor.link') }}</button>
        <button type="button" x-on:click="wrap('\n```\n', '\n```\n')" title="Blok kódu" class="rounded px-2 py-1 font-mono text-xs text-zinc-600 transition hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-800">```</button>

        <span class="ml-auto pr-1 text-[11px] text-zinc-400">{{ __('knowledge-base::kb.editor.markdown_hint') }}</span>
    </div>

    <textarea
        x-ref="area"
        wire:model="{{ $statePath }}"
        rows="24"
        spellcheck="true"
        class="w-full rounded-b-xl border border-zinc-200 bg-white p-4 font-mono text-sm leading-relaxed text-zinc-900 focus:border-sky-500 focus:ring-4 focus:ring-sky-500/10 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100"
    ></textarea>
</div>
