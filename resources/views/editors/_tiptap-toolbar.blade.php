{{--
    Panel TipTapu, sdílený samostatným editorem i polem uvnitř bloku.

    Jeden partial schválně: dvě sady tlačítek by znamenaly, že tučné písmo jde
    v článku udělat jinak než v upozornění — a jedna z nich by při první úpravě
    zůstala pozadu.

    Aktivní stav se čte z editoru (`active`), nedrží se vedle něj. Druhá kopie
    pravdy se rozejde ve chvíli, kdy někdo použije klávesovou zkratku.

    `$compact` schová to, co má v bloku vlastní typ nebo se do řádku nevejde
    (nadpisy, tabulky, blok kódu, undo). Zarovnání a formátování textu zůstává:
    to vlastní typ bloku nenahradí.
--}}
@php
    $compact = $compact ?? false;

    // Tabulka se v běžném bloku nenabízí – má vlastní typ. Uvnitř *toho*
    // typu je ale ovládání tabulky to jediné, oč jde.
    $tools = $tools ?? null;
    $tables = ! $compact || $tools === 'table';
    $btn = 'rounded px-2 py-1 text-xs transition hover:bg-zinc-200 dark:hover:bg-zinc-800';
    $on = 'bg-zinc-900 text-white hover:bg-zinc-900 dark:bg-white dark:text-zinc-900';
    $off = 'text-zinc-600 dark:text-zinc-300';

    // Pevná paleta, ne volný výběr barvy. Deset článků od tří lidí s libovolným
    // odstínem vypadá jako deset webů; tohle jsou barvy, které projdou i v
    // tmavém režimu.
    $palette = [
        'inherit' => __('knowledge-base::kb.editor.color_default'),
        '#dc2626' => __('knowledge-base::kb.editor.color_red'),
        '#ea580c' => __('knowledge-base::kb.editor.color_orange'),
        '#16a34a' => __('knowledge-base::kb.editor.color_green'),
        '#0284c7' => __('knowledge-base::kb.editor.color_blue'),
        '#7c3aed' => __('knowledge-base::kb.editor.color_purple'),
        '#71717a' => __('knowledge-base::kb.editor.color_gray'),
    ];

    $highlights = [
        '#fef08a' => __('knowledge-base::kb.editor.color_yellow'),
        '#bbf7d0' => __('knowledge-base::kb.editor.color_green'),
        '#bfdbfe' => __('knowledge-base::kb.editor.color_blue'),
        '#fecaca' => __('knowledge-base::kb.editor.color_red'),
        '#e9d5ff' => __('knowledge-base::kb.editor.color_purple'),
    ];
@endphp

<div class="flex flex-wrap items-center gap-1 border-b border-zinc-200 bg-zinc-50 px-2 py-1.5 dark:border-zinc-800 dark:bg-zinc-950">
    @unless ($compact)
        {{-- Styl odstavce jedním seznamem, ne řadou přepínačů: stavy se
             vylučují (odstavec není zároveň nadpis) a seznam to říká sám,
             navíc ukáže, čím odstavec pod kurzorem je. V bloku se nenabízí —
             tam styl určuje typ bloku. --}}
        <select x-on:change="style($event.target.value)" :value="active.style"
            class="rounded border-zinc-300 py-0.5 pl-2 pr-6 text-xs text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200">
            @foreach ([
                'paragraph' => __('knowledge-base::kb.editor.style_paragraph'),
                'h2' => __('knowledge-base::kb.editor.style_h2'),
                'h3' => __('knowledge-base::kb.editor.style_h3'),
                'quote' => __('knowledge-base::kb.editor.style_quote'),
                'code' => __('knowledge-base::kb.editor.style_code'),
            ] as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    @endunless

    <select x-on:change="size($event.target.value); $event.target.value = ''"
        class="rounded border-zinc-300 py-0.5 pl-2 pr-6 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
        <option value="">{{ __('knowledge-base::kb.editor.font_size') }}</option>
        @foreach (['' => __('knowledge-base::kb.editor.size_default'), '0.875em' => __('knowledge-base::kb.editor.size_small'), '1.25em' => __('knowledge-base::kb.editor.size_large'), '1.5em' => __('knowledge-base::kb.editor.size_huge')] as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>

    {{-- Krátký vybraný seznam, ne volný výběr písma: v bázi, kterou plní víc
         lidí, je libovolná rodina nejrychlejší cesta k deseti různým webům.
         Tyhle tři drží čitelnost i v tmavém režimu. --}}
    <select x-on:change="family($event.target.value); $event.target.value = ''"
        class="rounded border-zinc-300 py-0.5 pl-2 pr-6 text-xs text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
        <option value="">{{ __('knowledge-base::kb.editor.font_family') }}</option>
        @foreach ([
            'reset' => __('knowledge-base::kb.editor.font_default'),
            'system-ui, sans-serif' => __('knowledge-base::kb.editor.font_sans'),
            'Georgia, serif' => __('knowledge-base::kb.editor.font_serif'),
            'ui-monospace, monospace' => __('knowledge-base::kb.editor.font_mono'),
        ] as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>

    <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>

    <button type="button" x-on:click="run(c => c.toggleBold())" :class="active.bold ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-bold">B</button>
    <button type="button" x-on:click="run(c => c.toggleItalic())" :class="active.italic ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} italic">I</button>
    <button type="button" x-on:click="run(c => c.toggleUnderline())" :class="active.underline ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} underline">U</button>
    <button type="button" x-on:click="run(c => c.toggleStrike())" :class="active.strike ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} line-through">S</button>
    <button type="button" x-on:click="run(c => c.toggleCode())" :class="active.code ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }} font-mono">&lt;/&gt;</button>
    <button type="button" x-on:click="run(c => c.toggleSubscript())" :class="active.sub ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}" title="{{ __('knowledge-base::kb.editor.subscript') }}">x<sub>2</sub></button>
    <button type="button" x-on:click="run(c => c.toggleSuperscript())" :class="active.sup ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}" title="{{ __('knowledge-base::kb.editor.superscript') }}">x<sup>2</sup></button>

    {{-- Barva písma a podbarvení: dva rozbalovací vzorníky, ne barevný kruh. --}}
    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button type="button" x-on:click="open = ! open" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.text_color') }}">A</button>
        <div x-show="open" x-cloak class="absolute left-0 top-8 z-30 flex gap-1 rounded-lg border border-zinc-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            @foreach ($palette as $value => $label)
                <button type="button" title="{{ $label }}"
                    x-on:click="color(@js($value)); open = false"
                    class="h-5 w-5 rounded-full border border-zinc-300 dark:border-zinc-600"
                    style="background: {{ $value === 'inherit' ? 'transparent' : $value }}"></button>
            @endforeach
        </div>
    </div>

    <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false">
        <button type="button" x-on:click="open = ! open" :class="active.highlight ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}" title="{{ __('knowledge-base::kb.editor.text_background') }}">▧</button>
        <div x-show="open" x-cloak class="absolute left-0 top-8 z-30 flex gap-1 rounded-lg border border-zinc-200 bg-white p-2 shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
            <button type="button" title="{{ __('knowledge-base::kb.editor.color_default') }}"
                x-on:click="run(c => c.unsetHighlight()); open = false"
                class="h-5 w-5 rounded-full border border-zinc-300 dark:border-zinc-600"></button>
            @foreach ($highlights as $value => $label)
                <button type="button" title="{{ $label }}"
                    x-on:click="run(c => c.setHighlight({ color: @js($value) })); open = false"
                    class="h-5 w-5 rounded-full border border-zinc-300 dark:border-zinc-600"
                    style="background: {{ $value }}"></button>
            @endforeach
        </div>
    </div>

    {{-- Vymazat formátování. Nejčastější potřeba po vložení z Wordu nebo
         z webu, kde se přinese barva, velikost i rodina písma naráz. Kromě
         značek se ruší i uzly, jinak by po vložení zůstal odstavec nadpisem. --}}
    <button type="button" x-on:click="run(c => c.unsetAllMarks().clearNodes())" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.clear_format') }}">
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 4h9M11 4 8 16M4 16h7M14 11l4 4M18 11l-4 4" /></svg>
    </button>

    <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>

    <button type="button" x-on:click="run(c => c.toggleBulletList())" :class="active.bullet ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}">&bull;</button>
    <button type="button" x-on:click="run(c => c.toggleOrderedList())" :class="active.ordered ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}">1.</button>
    <button type="button" x-on:click="run(c => c.toggleTaskList())" :class="active.task ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}" title="{{ __('knowledge-base::kb.editor.task_list') }}">
        <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m2 6 2 2 4-4M2 14l2 2 4-4M11 6h7M11 15h7" /></svg>
    </button>
    {{-- Odsazení jde jen uvnitř seznamu, tak se jinde ani nenabízí. --}}
    <button type="button" x-show="active.listItem" x-cloak x-on:click="run(c => c.sinkListItem('listItem'))" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.indent') }}">&rsaquo;&rsaquo;</button>
    <button type="button" x-show="active.listItem" x-cloak x-on:click="run(c => c.liftListItem('listItem'))" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.outdent') }}">&lsaquo;&lsaquo;</button>
    <button type="button" x-on:click="link()" :class="active.link ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}">{{ __('knowledge-base::kb.editor.link') }}</button>

    <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>

    {{-- Zarovnání jako SVG, ne Unicode: znaky jako ⯇ nebo ↺ půlka fontů nemá
         a vykreslí místo nich prázdný čtvereček.

         Na rozdíl od nadpisů a tabulek zůstává i v zúženém panelu: vystředěný
         odstavec je běžná věc i uvnitř upozornění, a bez tlačítka by ho v
         blokovém editoru nešlo udělat vůbec. --}}
    @foreach ([
        'left' => 'M3 5h14M3 9h9M3 13h14M3 17h9',
        'center' => 'M3 5h14M6 9h8M3 13h14M6 17h8',
        'right' => 'M3 5h14M8 9h9M3 13h14M8 17h9',
        'justify' => 'M3 5h14M3 9h14M3 13h14M3 17h14',
    ] as $align => $path)
        <button type="button" x-on:click="run(c => c.setTextAlign(@js($align)))" :class="active.align === @js($align) ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}" title="{{ __('knowledge-base::kb.editor.align_'.$align) }}">
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 22" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="{{ $path }}" /></svg>
        </button>
    @endforeach

    @unless ($compact)

        {{-- Jazyk a název souboru se ukážou, **jen když kurzor stojí v bloku
             kódu**: na zbytku panelu by to byly dva ovladače, které skoro
             pořád nemají co ovládat. Jazyk z nabídky, ne z volného pole —
             překlep zvýraznění tiše vypne a je to vidět až na hotové stránce. --}}
        <template x-if="active.codeBlock">
            <span class="inline-flex items-center gap-1">
                <select
                    x-on:change="run(c => c.updateAttributes('codeBlock', { language: $event.target.value || null }))"
                    :value="active.codeLanguage"
                    class="rounded border border-zinc-300 bg-transparent px-1.5 py-1 font-mono text-xs dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    title="{{ __('knowledge-base::kb.editor.block.code_language') }}"
                >
                    <option value="">{{ __('knowledge-base::kb.editor.block.no_language') }}</option>
                    @foreach (\NyonCode\KnowledgeBase\Support\Settings::array('editors.languages') as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>

                <input
                    type="text"
                    x-on:change="run(c => c.updateAttributes('codeBlock', { title: $event.target.value || null }))"
                    :value="active.codeTitle"
                    placeholder="{{ __('knowledge-base::kb.editor.block.code_title') }}"
                    class="w-40 rounded border border-zinc-300 bg-transparent px-1.5 py-1 font-mono text-xs dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                />
            </span>
        </template>
        {{-- V blokovém editoru na to je vlastní typ, tady jinak předěl udělat
             nejde – StarterKit ho umí, jen o něj nikdo nežádal. --}}
        <button type="button" x-on:click="run(c => c.setHorizontalRule())" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.horizontal_rule') }}">
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><path d="M3 10h14" /></svg>
        </button>

        <span class="mx-1 h-4 w-px bg-zinc-300 dark:bg-zinc-700"></span>

        <button type="button" x-on:click="$wire.openImagePicker()" class="{{ $btn }} {{ $off }}">{{ __('knowledge-base::kb.editor.image') }}</button>
    @endunless

    @if ($tables)
        <button type="button" x-on:click="run(c => c.insertTable({ rows: 3, cols: 3, withHeaderRow: true }))" class="{{ $btn }} {{ $off }}">{{ __('knowledge-base::kb.editor.table') }}</button>

        {{-- Ovládání tabulky se ukáže, jen když v ní kurzor stojí: deset
             tlačítek navíc na stránce bez tabulky je jen šum. --}}
        <template x-if="active.table">
            <span class="flex flex-wrap items-center gap-1 rounded bg-zinc-200/60 px-1 py-0.5 dark:bg-zinc-800/60">
                <button type="button" x-on:click="run(c => c.addRowBefore())" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.row_before') }}">↑+</button>
                <button type="button" x-on:click="run(c => c.addRowAfter())" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.row_after') }}">↓+</button>
                <button type="button" x-on:click="run(c => c.deleteRow())" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.row_delete') }}">&minus;{{ __('knowledge-base::kb.editor.row') }}</button>
                <button type="button" x-on:click="run(c => c.addColumnBefore())" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.column_before') }}">←+</button>
                <button type="button" x-on:click="run(c => c.addColumnAfter())" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.column_after') }}">→+</button>
                <button type="button" x-on:click="run(c => c.deleteColumn())" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.column_delete') }}">&minus;{{ __('knowledge-base::kb.editor.column') }}</button>
                <button type="button" x-on:click="run(c => c.toggleHeaderRow())" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.header_row') }}">⊤</button>
                <button type="button" x-on:click="run(c => c.mergeOrSplit())" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.merge_cells') }}">⊞</button>
                <button type="button" x-on:click="run(c => c.deleteTable())" class="{{ $btn }} {{ $off }}" title="{{ __('knowledge-base::kb.editor.table_delete') }}">&minus;{{ __('knowledge-base::kb.editor.table') }}</button>
            </span>
        </template>
    @endif

    {{-- Zpět a znovu patří i do bloku: Ctrl+Z sice funguje vždy, ale tlačítko
         je jediné, co o té možnosti řekne tomu, kdo zkratku nezná. --}}
    <span class="ml-auto flex items-center gap-1">
        {{-- Skryté znaky: kde je konec odstavce a kde jen zalomení řádku, se
             jinak pozná až na hotové stránce. --}}
        <button type="button" x-on:click="run(c => c.toggleInvisibleCharacters())" :class="active.invisible ? '{{ $on }}' : '{{ $off }}'" class="{{ $btn }}" title="{{ __('knowledge-base::kb.editor.invisible') }}">&para;</button>

        <button type="button" x-on:click="run(c => c.undo())" class="{{ $btn }} {{ $off }}" aria-label="{{ __('knowledge-base::kb.editor.undo') }}">
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 4 3 8l4 4M3 8h9a5 5 0 0 1 0 10h-4" /></svg>
        </button>
        <button type="button" x-on:click="run(c => c.redo())" class="{{ $btn }} {{ $off }}" aria-label="{{ __('knowledge-base::kb.editor.redo') }}">
            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m13 4 4 4-4 4M17 8H8a5 5 0 0 0 0 10h4" /></svg>
        </button>
    </span>
</div>
