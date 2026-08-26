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

    // Vypíše se **jen volba, která se liší od výchozí**. Zvýrazňovač dostává
    // volby jednoho bloku tak, že se kódu předřadí servisní komentář — a ten
    // se v ukázce ukáže pokaždé, když ho zvýrazňovač nesní (výpadek API,
    // jazyk, jehož komentáře nezná). Blok, kde autor nic nepřepnul, si tedy
    // to riziko nemá proč kupovat: chová se přesně jako dřív.
    $defaults = \NyonCode\KnowledgeBase\Support\Settings::array('editors.blocks.code');

    $options = [];

    foreach (['line_numbers' => 'lineNumbers', 'diff_indicators' => 'diffIndicators'] as $field => $option) {
        if (! array_key_exists($field, $data)) {
            continue;
        }

        $value = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);

        if ($value !== (bool) ($defaults[$field] ?? false)) {
            $options[$option] = $value;
        }
    }
@endphp
<pre class="kb-code"
    @if ($language) data-lang="{{ $language }}" @endif
    @if ($title) data-title="{{ $title }}" @endif
    @if ($options) data-torchlight="{{ json_encode($options) }}" @endif
><code @if ($language) class="language-{{ $language }}" @endif>{{ $data['text'] ?? '' }}</code></pre>
