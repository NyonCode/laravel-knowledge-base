{{-- Položka nese inline formátování (tučné, odkaz, `kód`), takže se
     vypisuje neescapovaně. Bezpečné to je proto, že celý sestavený blok
     ještě prochází sanitizérem v rendereru. --}}
<ol>
    @foreach ((array) ($data['items'] ?? []) as $entry)
        <li>{!! is_string($entry) ? $entry : ($entry['text'] ?? '') !!}</li>
    @endforeach
</ol>
