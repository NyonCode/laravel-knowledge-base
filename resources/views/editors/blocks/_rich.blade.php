{{--
    Formátovatelné pole uvnitř bloku — TipTap i s panelem.

    Bez panelu je to jen hezčí textarea: autor, který nezná zkratky, neumí
    zvýraznit slovo ani udělat seznam. Panel je proto tentýž jako v samostatném
    editoru, jen zúžený — v bloku se píše odstavec, ne stránka.

    Když hostitel TipTap nedodal, spadne se na textareu. Editor, který se
    nenačte, by byl horší.

    `wire:key` nese index **i název pole**: bloky se přesouvají a mažou a bez
    toho by po přeskládání zůstal editor viset nad cizí hodnotou.
--}}
@php
    $statePath = 'blockData.'.$index.'.'.$field;
    $value = $this->blockData[$index][$field] ?? '';
    $rich = \NyonCode\KnowledgeBase\Support\Settings::bool('editors.tiptap.bundled');

    // Předvolba: prázdný blok se rovnou otevře jako seznam, aby autor nemusel
    // v bloku „Kroky“ hledat tlačítko na číslování.
    $preset = $preset ?? null;

    // Nástroje navíc pro blok, jehož jádrem jsou (tabulka).
    $tools = $tools ?? null;
@endphp

@if ($rich)
    <div
        wire:key="kb-rich-{{ $index }}-{{ $field }}"
        x-data="{
            editor: null,
            active: {},
            uploading: false,
            // Dokud se editor nenamontuje, drží se textarea. Prázdný rámeček
            // by vypadal jako rozbité pole a autor by neměl kam psát —
            // nejčastěji se to stane, když hostitel bundle nenasadil.
            ready: false,

            init() {
                this.$nextTick(() => {
                    if (typeof window.kbEditor !== 'function') {
                        return
                    }

                    this.ready = true

                    this.editor = window.kbEditor(this.$refs.surface, {
                        content: @js($value),
                        onChange: (html) => $wire.set(@js($statePath), html, false),
                        // `compact`: úchyt by v bloku soupeřil s přetahováním
                        // samotných bloků a nabídka vložení s jejich paletou.
                        compact: true,
                        bubble: this.$refs.bubble,
                        upload: (file) => this.uploadImage(file),
                        mentions: @js($this->mentionTargets()),
                        placeholder: @js(__('knowledge-base::kb.editor.placeholder')),
                    })

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
                            bullet: this.raw().isActive('bulletList'),
                            ordered: this.raw().isActive('orderedList'),
                            listItem: this.raw().isActive('listItem'),
                            task: this.raw().isActive('taskList'),
                            link: this.raw().isActive('link'),
                            style: this.raw().isActive('codeBlock') ? 'code'
                                : this.raw().isActive('blockquote') ? 'quote'
                                : this.raw().isActive('heading', { level: 2 }) ? 'h2'
                                : this.raw().isActive('heading', { level: 3 }) ? 'h3'
                                : 'paragraph',
                            invisible: this.raw().storage.invisibleCharacters?.visibility?.() ?? false,
                            // Zarovnání se nedá zjistit jedním dotazem: je to
                            // atribut uzlu, ne značka, tak se zkusí hodnoty.
                            align: ['left', 'center', 'right', 'justify']
                                .find(a => this.raw().isActive({ textAlign: a })) ?? null,
                        }
                    }

                    this.raw().on('transaction', sync)
                    sync()

                    @if ($preset)
                        // Jen do prázdného bloku: u rozepsaného obsahu by
                        // předvolba přepsala, co v něm autor má.
                        if (this.raw().isEmpty) {
                            this.run((chain) => chain.toggle{{ ucfirst($preset) }}())
                        }
                    @endif
                })
            },


            /*
             * Styl odstavce jedním výběrem.
             *
             * „Normální“ nesundá jen nadpis, ale i citaci: kdo v seznamu vybere
             * normální text, čeká prostý odstavec, ne odstavec pořád zapíchnutý
             * v citaci.
             */
            style(value) {
                const chain = this.raw().chain().focus()

                if (value === 'h2' || value === 'h3') {
                    chain.setNode('heading', { level: value === 'h2' ? 2 : 3 })
                } else if (value === 'quote') {
                    chain.setBlockquote()
                } else if (value === 'code') {
                    chain.setCodeBlock()
                } else {
                    chain.setParagraph().unsetBlockquote()
                }

                chain.run()
            },

            /* Rodina písma; prázdná hodnota je hlavička seznamu, ne volba. */
            family(value) {
                if (value === '') {
                    return
                }

                value === 'reset'
                    ? this.run((chain) => chain.unsetFontFamily())
                    : this.run((chain) => chain.setFontFamily(value))
            },

            /**
             * Nahraje soubor a vrátí adresu.
             *
             * Nahrávání vlastní Livewire (jedna vlastnost, validace i úložiště na
             * serveru), ale TipTap potřebuje adresu zpátky v JavaScriptu, aby obrázek
             * vložil přesně tam, kam ho autor pustil. Server ji po uložení rozešle
             * událostí `kb-image-picked`; tenhle slib na ni jednou počká.
             */
            uploadImage(file) {
                return new Promise((resolve, reject) => {
                    const done = (event) => {
                        window.removeEventListener('kb-image-picked', done)
                        this.uploading = false
                        resolve(event.detail.url)
                    }

                    const failed = () => {
                        window.removeEventListener('kb-image-picked', done)
                        this.uploading = false
                        reject(new Error('upload failed'))
                    }

                    window.addEventListener('kb-image-picked', done)
                    this.uploading = true

                    $wire.upload('imageUpload', file, () => {}, failed)
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

            run(command) { command(this.raw().chain().focus()).run() },

            link() {
                const previous = this.raw().getAttributes('link').href ?? ''
                const href = window.prompt(@js(__('knowledge-base::kb.editor.link_prompt')), previous)

                if (href === null) return

                // Prázdné pole odkaz odebere; zrušený dialog (null) ho nechá být.
                href === ''
                    ? this.run((chain) => chain.extendMarkRange('link').unsetLink())
                    : this.run((chain) => chain.extendMarkRange('link').setLink({ href }))
            },

            destroy() { this.editor?.destroy() },
        }"
        class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-800"
    >
        <div x-show="ready" x-cloak>
            @include('knowledge-base::editors._tiptap-toolbar', ['compact' => true, 'tools' => $tools])
        </div>

        <textarea x-show="! ready" rows="3" wire:model.blur="{{ $statePath }}"
            class="w-full border-0 p-3 text-sm focus:ring-0 dark:bg-zinc-950 dark:text-zinc-100"></textarea>

        <template x-if="ready">
            <div>@include('knowledge-base::editors._bubble-menu')</div>
        </template>

        <div wire:ignore x-show="ready">
            <div
                x-ref="surface"
                data-testid="kb-block-rich-{{ $index }}"
                class="kb-prose prose prose-sm max-w-none p-3 focus:outline-none dark:prose-invert"
            ></div>
        </div>
    </div>
@else
    <textarea rows="3" wire:model.blur="{{ $statePath }}"
        class="w-full rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
@endif
