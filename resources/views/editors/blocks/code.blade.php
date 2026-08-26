@php
    $languages = \NyonCode\KnowledgeBase\Support\Settings::array('editors.languages');
    $field = 'blockData.'.$index;
@endphp

<div class="mb-2 flex flex-wrap gap-2">
    {{-- Jazyk z nabídky, ne z volného pole: překlep (`js` místo `javascript`)
         zvýraznění tiše vypne — blok se vykreslí, jen je černobílý, takže si
         toho autor všimne až na hotové stránce. --}}
    <select wire:model.blur="{{ $field }}.language"
        class="w-44 rounded-lg border border-zinc-200 px-2 py-2 font-mono text-xs dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100">
        <option value="">{{ __('knowledge-base::kb.editor.block.no_language') }}</option>
        @foreach ($languages as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>

    {{-- Název souboru. U ukázky kódu je to půlka informace: čtenář musí vědět,
         *kam* se to píše, ne jen *co* se píše. --}}
    <input type="text" wire:model.blur="{{ $field }}.title"
        placeholder="{{ __('knowledge-base::kb.editor.block.code_title') }}"
        class="min-w-48 flex-1 rounded-lg border border-zinc-200 px-3 py-2 font-mono text-xs dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100" />
</div>

<textarea rows="6" wire:model.blur="{{ $field }}.text" spellcheck="false"
    class="w-full rounded-lg border border-zinc-200 p-3 font-mono text-sm dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-100"></textarea>

<div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-zinc-500 dark:text-zinc-400">
    <label class="inline-flex items-center gap-1.5">
        <input type="checkbox" wire:model.blur="{{ $field }}.line_numbers"
            class="rounded border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950" />
        {{ __('knowledge-base::kb.editor.block.line_numbers') }}
    </label>

    <label class="inline-flex items-center gap-1.5">
        <input type="checkbox" wire:model.blur="{{ $field }}.diff_indicators"
            class="rounded border-zinc-300 dark:border-zinc-700 dark:bg-zinc-950" />
        {{ __('knowledge-base::kb.editor.block.diff_indicators') }}
    </label>

    {{-- Anotace se píšou do kódu, ne zaškrtávají: platí vždy na konkrétní
         řádek, takže volba bloku pro ně není.

         Ukazuje se zápis **pro vybraný jazyk**, ne obecný tvar: anotace musí
         být skutečný komentář, jinak se nezpracuje a zůstane v ukázce viset
         jako text — a autor to zjistí až na hotové stránce. V JSONu je to
         `// [tl! focus]`, v shellu `# [tl! focus]`, v HTML `<!-- [tl! focus] -->`. --}}
    @php
        $lang = $this->blockData[$index]['language'] ?? null;
        $sample = fn (string $a) => \NyonCode\KnowledgeBase\Support\CodeComment::annotation($lang, $a);
    @endphp
    <span class="basis-full">
        {{ __('knowledge-base::kb.editor.block.code_annotations') }}
        <span class="ml-1 font-mono text-zinc-600 dark:text-zinc-300">
            {{ $sample('[tl! ++]') }} · {{ $sample('[tl! --]') }} ·
            {{ $sample('[tl! focus]') }} · {{ $sample('[tl! collapse]') }}
        </span>
    </span>
</div>

{{-- Náhled toutéž cestou jako hotová stránka, ne přibližný.

     Zvýraznění, anotace i volby bloku vyhodnocuje až server, takže
     z textarey se výsledek odhadnout nedá — a náhled, který by ho jen
     napodobil, by lhal právě v tom, kvůli čemu se do něj člověk dívá.
     Prázdný blok náhled nemá; viz ArticleEditor::previewFor(). --}}
@php $codePreview = $this->previewFor($index); @endphp

@if ($codePreview)
    <div class="mt-3">
        <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-zinc-400 dark:text-zinc-500">
            {{ __('knowledge-base::kb.editor.block.code_preview') }}
        </p>
        <div class="kb-prose">{!! $codePreview !!}</div>
    </div>
@endif
