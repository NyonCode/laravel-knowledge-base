{{--
    Formátovatelné pole uvnitř bloku.

    Sdílené prozaickými bloky (text, upozornění, citace, pojem, odpověď na
    otázku) — psát do nich holou textareu znamená, že autor buď neumí zvýraznit
    slovo, nebo se musí naučit markdown, který ve zbytku blokového editoru
    nikde jinde není.

    Když hostitel TipTap nedodal, spadne se na textareu. Editor, který se
    nenačte, by byl horší než ta textarea.

    Každá instance má vlastní `wire:key` s indexem **i názvem pole**: bloky se
    přesouvají a mažou a bez toho by po přeskládání zůstal editor viset nad
    cizí hodnotou.
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
            init() {
                this.$nextTick(() => {
                    if (typeof window.kbEditor !== 'function') {
                        return
                    }

                    this.editor = window.kbEditor(this.$refs.surface, {
                        content: @js($value),
                        onChange: (html) => $wire.set(@js($statePath), html, false),
                    })
                })
            },
            destroy() { this.editor?.destroy() },
        }"
        class="rounded-lg border border-zinc-200 dark:border-zinc-800"
    >
        <div wire:ignore>
            <div
                x-ref="surface"
                data-testid="kb-block-rich-{{ $index }}"
                class="kb-prose prose prose-sm max-w-none p-3 focus:outline-none dark:prose-invert"
            ></div>
        </div>
    </div>
@else
    <textarea rows="3" wire:model.blur="{{ $statePath }}" @if ($placeholder ?? false) placeholder="{{ $placeholder }}" @endif
        class="w-full rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
@endif
