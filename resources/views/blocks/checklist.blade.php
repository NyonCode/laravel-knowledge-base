{{-- Statická odškrtávátka: stav se nikam neukládá a nemá — návod čte deset
     lidí a zaškrtnutí jednoho z nich není pravda pro ostatní. --}}
{{-- Položka nese inline formátování (tučné, odkaz, `kód`), takže se
     vypisuje neescapovaně. Bezpečné to je proto, že celý sestavený blok
     ještě prochází sanitizérem v rendereru. --}}
<ul class="kb-checklist">
    @foreach ((array) ($data['items'] ?? []) as $entry)
        <li>{!! is_string($entry) ? $entry : ($entry['text'] ?? '') !!}</li>
    @endforeach
</ul>
