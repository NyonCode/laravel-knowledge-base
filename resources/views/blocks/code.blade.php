{{--
    Ukázka kódu.

    Zvýraznění tady **není** – blok jen popíše, co v něm je, a zvýrazňovač
    hostitele si to přečte z `data-*` na `<pre>` (u nás Torchlight; viz
    `App\Support\Highlighting\TorchlightHighlighter`). Balíček tak nezávisí na
    žádném konkrétním zvýrazňovači a instalace bez něj vykreslí čitelný,
    jen nebarevný blok.

    `data-torchlight` jsou volby **jednoho** bloku. Klient je posílá jednou za
    dávku, takže „čísla řádků jen tady" jde říct jen takhle; prázdné se
    nevypisuje, aby se blok bez voleb choval přesně jako dřív.
--}}
@php
    $language = trim((string) ($data['language'] ?? '')) ?: null;
    $title = trim((string) ($data['title'] ?? '')) ?: null;

    $options = array_filter(
        [
            'lineNumbers' => $data['line_numbers'] ?? null,
            'diffIndicators' => $data['diff_indicators'] ?? null,
            'torchlightAnnotations' => $data['annotations'] ?? null,
        ],
        static fn ($value) => $value !== null && $value !== ''
    );

    $options = array_map(
        static fn ($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
        $options
    );
@endphp
<pre class="kb-code"
    @if ($language) data-lang="{{ $language }}" @endif
    @if ($title) data-title="{{ $title }}" @endif
    @if ($options) data-torchlight="{{ json_encode($options) }}" @endif
><code @if ($language) class="language-{{ $language }}" @endif>{{ $data['text'] ?? '' }}</code></pre>
