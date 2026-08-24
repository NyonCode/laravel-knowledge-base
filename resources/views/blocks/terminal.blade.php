{{--
    Příkaz do terminálu.

    Jazyk je pevně `bash`: blok se jmenuje „Příkaz" a nic jiného v něm nemá
    být, takže vybírat ho by byla otázka bez druhé odpovědi. Zvýraznění se
    přesto hodí — v `git commit -m "…"` je vidět, kde končí přepínač a začíná
    text.

    Vlastní vzhled (`.kb-terminal`) drží `data-kb-block`, ne třída: zvýrazňovač
    vrací vlastní `<pre>` a přenáší přes něj jen `data-*`, takže třída by se
    ztratila a terminál by po zapnutí zvýraznění vypadal jako obyčejný blok.
--}}
<pre class="kb-terminal" data-kb-block="terminal" data-lang="bash"><code class="language-bash">{{ $data['text'] ?? '' }}</code></pre>
