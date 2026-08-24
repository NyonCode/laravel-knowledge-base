{{--
    TipTap plocha.

    Balíček ships jen tenhle obal — knihovnu dodává hostitel jako
    `window.kbEditor(el, { content, onChange })`. Zabalit ji sem by znamenalo
    druhou kopii ProseMirroru v aplikacích, které už jednu mají, a připnutou
    verzi pro všechny. Dokud globál není, ovladač se vůbec nenabídne
    ({@see \NyonCode\KnowledgeBase\Editors\RichTextEditor::isAvailable()}).

    `wire:ignore` je nutné: bez něj by morph přepsal DOM, který drží
    ProseMirror, a kurzor by při každé aktualizaci skákal na začátek. Cenou je,
    že plochu pak Livewire nevymění ani při přepnutí editoru — proto ji obalový
    pohled klíčuje názvem ovladače.
--}}
<div
    data-testid="kb-editor-rich-text"
    x-data="{
        editor: null,
        active: {},

        init() {
            // `$nextTick`: v `init()` rodiče ještě `$refs` potomků nejsou
            // naplněné a TipTap by dostal `undefined` místo elementu.
            this.$nextTick(() => {
                if (typeof window.kbEditor !== 'function') {
                    return
                }

                this.editor = window.kbEditor(this.$refs.surface, {
                    content: @js($this->{$statePath}),
                    // `false` = neposílat request; hodnota odejde se zbytkem
                    // formuláře. Request na každý úhoz by z psaní udělal čekání.
                    onChange: (html) => $wire.set('{{ $statePath }}', html, false),
                })

                // Stav panelu se čte z editoru, nedrží se vedle něj: druhá
                // kopie pravdy se rozejde v okamžiku, kdy někdo použije zkratku.
                const sync = () => {
                    this.active = {
                        bold: this.editor.isActive('bold'),
                        italic: this.editor.isActive('italic'),
                        code: this.editor.isActive('code'),
                        h2: this.editor.isActive('heading', { level: 2 }),
                        h3: this.editor.isActive('heading', { level: 3 }),
                        bullet: this.editor.isActive('bulletList'),
                        ordered: this.editor.isActive('orderedList'),
                        quote: this.editor.isActive('blockquote'),
                        codeBlock: this.editor.isActive('codeBlock'),
                        link: this.editor.isActive('link'),
                    }
                }

                this.editor.on('transaction', sync)
                sync()
            })
        },

        run(command) {
            command(this.editor.chain().focus()).run()
        },

        link() {
            const previous = this.editor.getAttributes('link').href ?? ''
            const href = window.prompt(@js(__('knowledge-base::kb.editor.link_prompt')), previous)

            if (href === null) {
                return
            }

            // Prázdné pole = odebrat odkaz. Zrušení dialogu (null) se od toho
            // liší a nesmí odkaz smazat.
            if (href === '') {
                this.run((chain) => chain.extendMarkRange('link').unsetLink())

                return
            }

            this.run((chain) => chain.extendMarkRange('link').setLink({ href }))
        },

        image() {
            const src = window.prompt(@js(__('knowledge-base::kb.editor.image_prompt')), '')

            if (src) {
                this.run((chain) => chain.setImage({ src }))
            }
        },

        destroy() {
            this.editor?.destroy()
        },
    }"
    class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
>
    <div class="flex flex-wrap items-center gap-1 border-b border-zinc-200 bg-zinc-50 px-2 py-1.5 dark:border-zinc-800 dark:bg-zinc-950">
        @php
            $btn = 'rounded px-2 py-1 text-xs transition hover:bg-zinc-200 dark:hover:bg-zinc-800';
            $on = 'bg-zinc-900 text-white hover:bg-zinc-900 dark:bg-white dark:text-zinc-900';
            $off = 'text-zinc-600 dark:text-zinc-300';
        @endphp

        <button type="button" x-on:click="run(c => c.toggleHeading({ level: 2 }))" :class="active.h2 ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-semibold">H2</button>
        <button type="button" x-on:click="run(c => c.toggleHeading({ level: 3 }))" :class="active.h3 ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-semibold">H3</button>

        <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>

        <button type="button" x-on:click="run(c => c.toggleBold())" :class="active.bold ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-bold">B</button>
        <button type="button" x-on:click="run(c => c.toggleItalic())" :class="active.italic ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} italic">I</button>
        <button type="button" x-on:click="run(c => c.toggleCode())" :class="active.code ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-mono">&lt;/&gt;</button>

        <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>

        <button type="button" x-on:click="run(c => c.toggleBulletList())" :class="active.bullet ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}">&bull;&nbsp;{{ __('knowledge-base::kb.editor.list') }}</button>
        <button type="button" x-on:click="run(c => c.toggleOrderedList())" :class="active.ordered ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}">1.</button>
        <button type="button" x-on:click="run(c => c.toggleBlockquote())" :class="active.quote ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}">&ldquo;</button>
        <button type="button" x-on:click="run(c => c.toggleCodeBlock())" :class="active.codeBlock ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-mono">```</button>

        <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>

        <button type="button" x-on:click="link()" :class="active.link ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}">{{ __('knowledge-base::kb.editor.link') }}</button>
        <button type="button" x-on:click="image()" class="{{ $btn }} {{ $off }}">{{ __('knowledge-base::kb.editor.image') }}</button>
        <button type="button" x-on:click="run(c => c.insertTable({ rows: 3, cols: 3, withHeaderRow: true }))" class="{{ $btn }} {{ $off }}">{{ __('knowledge-base::kb.editor.table') }}</button>

        <span class="ml-auto flex items-center gap-1">
            <button type="button" x-on:click="run(c => c.undo())" class="{{ $btn }} {{ $off }}" aria-label="{{ __('knowledge-base::kb.editor.undo') }}">&#8630;</button>
            <button type="button" x-on:click="run(c => c.redo())" class="{{ $btn }} {{ $off }}" aria-label="{{ __('knowledge-base::kb.editor.redo') }}">&#8631;</button>
        </span>
    </div>

    <div wire:ignore>
        <div
            x-ref="surface"
            class="kb-prose prose prose-zinc min-h-[24rem] max-w-none p-4 focus:outline-none dark:prose-invert"
        ></div>
    </div>
</div>
