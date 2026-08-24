{{--
    Bublina nad označeným textem.

    Nenahrazuje panel, zkracuje cestu: nejčastější úpravy jsou na označeném
    textu a cesta okem nahoru a myší zpátky je to, co dělá psaní v editoru
    pomalejší než ve Wordu. Je proto úmyslně krátká — co se sem vejde všechno,
    to už je druhý panel, který se s prvním rozejde.

    Viditelnost řídí TipTap (`BubbleMenu`), ne Alpine: plugin ví o výběru,
    `x-show` ne.
--}}
<div x-ref="bubble" class="kb-bubble" role="toolbar" aria-label="{{ __('knowledge-base::kb.editor.selection_tools') }}">
    <button type="button" x-on:click="run(c => c.toggleBold())" :class="active.bold ? 'is-on' : ''" class="kb-bubble-item font-bold">B</button>
    <button type="button" x-on:click="run(c => c.toggleItalic())" :class="active.italic ? 'is-on' : ''" class="kb-bubble-item italic">I</button>
    <button type="button" x-on:click="run(c => c.toggleUnderline())" :class="active.underline ? 'is-on' : ''" class="kb-bubble-item underline">U</button>
    <button type="button" x-on:click="run(c => c.toggleCode())" :class="active.code ? 'is-on' : ''" class="kb-bubble-item font-mono">&lt;/&gt;</button>
    <button type="button" x-on:click="run(c => c.toggleHighlight({ color: '#fef08a' }))" :class="active.highlight ? 'is-on' : ''" class="kb-bubble-item">▧</button>
    <button type="button" x-on:click="link()" :class="active.link ? 'is-on' : ''" class="kb-bubble-item">{{ __('knowledge-base::kb.editor.link') }}</button>
    <button type="button" x-on:click="run(c => c.unsetAllMarks())" class="kb-bubble-item" title="{{ __('knowledge-base::kb.editor.clear_format') }}">
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 4h9M11 4 8 16M4 16h7M14 11l4 4M18 11l-4 4" /></svg>
    </button>
</div>
