{{--
    TipTap surface.

    The package ships this shell, not the library: the host bundles TipTap and
    exposes `window.kbEditor(el, { content, onChange })`. Without it the driver
    is never offered (see RichTextEditor::isAvailable), so this view can assume
    the global exists.

    `wire:ignore` is load-bearing — Livewire's morph would otherwise replace
    the DOM ProseMirror is holding on to and the caret would jump on every
    keystroke. The value travels back through `$wire.set`, never through the
    morph.
--}}
<div
    data-testid="kb-editor-rich-text"
    wire:ignore
    x-data="{
        editor: null,

        init() {
            // `$nextTick`, ne rovnou: v `init()` rodiče ještě `$refs` potomků
            // nejsou naplněné a TipTap by dostal `undefined` místo elementu.
            this.$nextTick(() => {
                if (typeof window.kbEditor !== 'function') {
                    return
                }

                this.editor = window.kbEditor(this.$refs.surface, {
                    content: @js($this->{$statePath}),
                    // `false` = neposílat request; hodnota se odešle až se
                    // zbytkem formuláře. Request na každý úhoz by z psaní
                    // udělal čekání.
                    onChange: (html) => $wire.set('{{ $statePath }}', html, false),
                })
            })
        },

        destroy() {
            this.editor?.destroy()
        },
    }"
    class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900"
>
    <div
        x-ref="surface"
        class="kb-prose prose prose-zinc min-h-[24rem] max-w-none p-4 focus:outline-none dark:prose-invert"
    ></div>
</div>
