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
        uploading: false,
        ready: false,

        init() {
            // `$nextTick`: v `init()` rodiče ještě `$refs` potomků nejsou
            // naplněné a TipTap by dostal `undefined` místo elementu.
            this.$nextTick(() => {
                if (typeof window.kbEditor !== 'function') {
                    return
                }

                this.ready = true

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
                        bold: this.raw().isActive('bold'),
                        italic: this.raw().isActive('italic'),
                        underline: this.raw().isActive('underline'),
                        strike: this.raw().isActive('strike'),
                        code: this.raw().isActive('code'),
                        sub: this.raw().isActive('subscript'),
                        sup: this.raw().isActive('superscript'),
                        highlight: this.raw().isActive('highlight'),
                        h2: this.raw().isActive('heading', { level: 2 }),
                        h3: this.raw().isActive('heading', { level: 3 }),
                        bullet: this.raw().isActive('bulletList'),
                        ordered: this.raw().isActive('orderedList'),
                        listItem: this.raw().isActive('listItem'),
                        task: this.raw().isActive('taskList'),
                        quote: this.raw().isActive('blockquote'),
                        codeBlock: this.raw().isActive('codeBlock'),
                        link: this.raw().isActive('link'),
                        table: this.raw().isActive('table'),
                        align: ['left', 'center', 'right', 'justify'].find(a => this.raw().isActive({ textAlign: a })) ?? null,
                    }
                }

                this.raw().on('transaction', sync)
                sync()
            })
        },


        /* Barva písma; „inherit“ ji odebere. */
        color(value) {
            value === 'inherit' || value === ''
                ? this.run((chain) => chain.unsetTextColor())
                : this.run((chain) => chain.setTextColor(value))
        },

        /* Velikost písma v em, ne v px: článek se čte i na telefonu. */
        size(value) {
            value === ''
                ? this.run((chain) => chain.unsetFontSize())
                : this.run((chain) => chain.setFontSize(value))
        },

        /*
         * Syrová instance editoru, ne ta z reaktivních dat.
         *
         * Alpine drží stav komponenty v proxy a obalí do něj i editor.
         * ProseMirror ale u každé transakce porovnává **identitu** stavu,
         * ze kterého vznikla, se stavem, na který se aplikuje — přes proxy
         * se rozejdou a příkaz skončí na „Applying a mismatched
         * transaction“. Navenek to vypadá, že tlačítko nic nedělá.
         *
         * Musí to být metoda, ne `get`: hodnotu vrácenou z getteru by
         * Alpine obalil znovu, kdežto návratovou hodnotu volání nechává být.
         */
        raw() {
            return window.Alpine?.raw(this.editor) ?? this.editor
        },

        run(command) {
            command(this.raw().chain().focus()).run()
        },

        link() {
            const previous = this.raw().getAttributes('link').href ?? ''
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

        insertImage(src) {
            if (src) {
                this.run((chain) => chain.setImage({ src }))
            }
        },

        /**
         * Soubor upuštěný přímo na plochu se nahraje bez otevírání výběru.
         * Přetažení **je** to potvrzení; modálka by tu byla krok navíc.
         */
        dropped(event) {
            const file = event.dataTransfer?.files?.[0]

            if (! file || ! file.type.startsWith('image/')) {
                return
            }

            event.preventDefault()
            this.uploading = true

            $wire.upload('imageUpload', file, () => { this.uploading = false })
        },

        destroy() {
            this.editor?.destroy()
        },
    }"
    x-on:kb-image-picked.window="insertImage($event.detail.url)"
    class="relative overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
>
    <div x-show="ready" x-cloak>
        @include('knowledge-base::editors._tiptap-toolbar')
    </div>

    {{-- Bez načteného bundle zůstane pole použitelné, jen bez formátování. --}}
    <textarea x-show="! ready" rows="20" wire:model.blur="{{ $statePath }}"
        class="w-full border-0 p-4 font-mono text-sm focus:ring-0 dark:bg-zinc-900 dark:text-zinc-100"></textarea>

    <div wire:ignore x-show="ready" x-on:drop="dropped($event)" x-on:dragover.prevent>
        <div
            x-ref="surface"
            class="kb-prose prose prose-zinc min-h-[24rem] max-w-none p-4 focus:outline-none dark:prose-invert"
        ></div>
    </div>

    <div x-show="uploading" x-cloak
         class="pointer-events-none absolute inset-x-0 bottom-0 bg-sky-600/90 px-4 py-1.5 text-center text-xs font-medium text-white">
        {{ __('knowledge-base::kb.editor.uploading') }}
    </div>
</div>
