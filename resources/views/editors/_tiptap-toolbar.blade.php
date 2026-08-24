{{--
    Panel TipTapu, sdílený samostatným editorem i polem uvnitř bloku.

    Jeden partial schválně: dvě sady tlačítek by znamenaly, že tučné písmo jde
    v článku udělat jinak než v upozornění — a jedna z nich by při první úpravě
    zůstala pozadu.

    Aktivní stav se čte z editoru (`active`), nedrží se vedle něj. Druhá kopie
    pravdy se rozejde ve chvíli, kdy někdo použije klávesovou zkratku.

    `$compact` schová to, co se do bloku nevejde (tabulky, obrázky, undo):
    v bloku se píše odstavec, ne stránka.
--}}
@php
    $compact = $compact ?? false;
    $btn = 'rounded px-2 py-1 text-xs transition hover:bg-zinc-200 dark:hover:bg-zinc-800';
    $on = 'bg-zinc-900 text-white hover:bg-zinc-900 dark:bg-white dark:text-zinc-900';
    $off = 'text-zinc-600 dark:text-zinc-300';
@endphp

<div class="flex flex-wrap items-center gap-1 border-b border-zinc-200 bg-zinc-50 px-2 py-1.5 dark:border-zinc-800 dark:bg-zinc-950">
    @unless ($compact)
        <button type="button" x-on:click="run(c => c.toggleHeading({ level: 2 }))" :class="active.h2 ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-semibold">H2</button>
        <button type="button" x-on:click="run(c => c.toggleHeading({ level: 3 }))" :class="active.h3 ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-semibold">H3</button>
        <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>
    @endunless

    <button type="button" x-on:click="run(c => c.toggleBold())" :class="active.bold ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-bold">B</button>
    <button type="button" x-on:click="run(c => c.toggleItalic())" :class="active.italic ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} italic">I</button>
    <button type="button" x-on:click="run(c => c.toggleStrike())" :class="active.strike ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} line-through">S</button>
    <button type="button" x-on:click="run(c => c.toggleCode())" :class="active.code ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-mono">&lt;/&gt;</button>

    <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>

    <button type="button" x-on:click="run(c => c.toggleBulletList())" :class="active.bullet ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}">&bull;</button>
    <button type="button" x-on:click="run(c => c.toggleOrderedList())" :class="active.ordered ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}">1.</button>
    <button type="button" x-on:click="link()" :class="active.link ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}">{{ __('knowledge-base::kb.editor.link') }}</button>

    @unless ($compact)
        <button type="button" x-on:click="run(c => c.toggleBlockquote())" :class="active.quote ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}">&ldquo;</button>
        <button type="button" x-on:click="run(c => c.toggleCodeBlock())" :class="active.codeBlock ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-mono">```</button>

        <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>

        <button type="button" x-on:click="$wire.openImagePicker()" class="{{ $btn }} {{ $off }}">{{ __('knowledge-base::kb.editor.image') }}</button>
        <button type="button" x-on:click="run(c => c.insertTable({ rows: 3, cols: 3, withHeaderRow: true }))" class="{{ $btn }} {{ $off }}">{{ __('knowledge-base::kb.editor.table') }}</button>
        <button type="button" x-show="active.table" x-cloak x-on:click="run(c => c.addRowAfter())" class="{{ $btn }} {{ $off }}">+{{ __('knowledge-base::kb.editor.row') }}</button>
        <button type="button" x-show="active.table" x-cloak x-on:click="run(c => c.addColumnAfter())" class="{{ $btn }} {{ $off }}">+{{ __('knowledge-base::kb.editor.column') }}</button>
        <button type="button" x-show="active.table" x-cloak x-on:click="run(c => c.deleteTable())" class="{{ $btn }} {{ $off }}">&minus;{{ __('knowledge-base::kb.editor.table') }}</button>

        <span class="ml-auto flex items-center gap-1">
            <button type="button" x-on:click="run(c => c.undo())" class="{{ $btn }} {{ $off }}" aria-label="{{ __('knowledge-base::kb.editor.undo') }}">&#8630;</button>
            <button type="button" x-on:click="run(c => c.redo())" class="{{ $btn }} {{ $off }}" aria-label="{{ __('knowledge-base::kb.editor.redo') }}">&#8631;</button>
        </span>
    @endunless
</div>
