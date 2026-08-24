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
@endphp

@if ($rich)
    <div
        wire:key="kb-rich-{{ $index }}-{{ $field }}"
        x-data="{
            editor: null,
            active: {},
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
                    })

                    const sync = () => {
                        this.active = {
                            bold: this.editor.isActive('bold'),
                            italic: this.editor.isActive('italic'),
                            underline: this.editor.isActive('underline'),
                            strike: this.editor.isActive('strike'),
                            code: this.editor.isActive('code'),
                            highlight: this.editor.isActive('highlight'),
                            bullet: this.editor.isActive('bulletList'),
                            ordered: this.editor.isActive('orderedList'),
                            listItem: this.editor.isActive('listItem'),
                            task: this.editor.isActive('taskList'),
                            link: this.editor.isActive('link'),
                        }
                    }

                    this.editor.on('transaction', sync)
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

            run(command) { command(this.editor.chain().focus()).run() },

            link() {
                const previous = this.editor.getAttributes('link').href ?? ''
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
            @include('knowledge-base::editors._tiptap-toolbar', ['compact' => true])
        </div>

        <textarea x-show="! ready" rows="3" wire:model.blur="{{ $statePath }}"
            class="w-full border-0 p-3 text-sm focus:ring-0 dark:bg-zinc-950 dark:text-zinc-100"></textarea>

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
