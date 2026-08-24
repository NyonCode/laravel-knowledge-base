{{-- Statická odškrtávátka: stav se nikam neukládá a nemá — návod čte deset
     lidí a zaškrtnutí jednoho z nich není pravda pro ostatní. --}}
{{-- Blok uložený dnešním editorem nese hotové HTML; starší tvar (řádek na
     položku) se pořád vykreslí, protože se převádí až při editaci. Obojí
     projde sanitizérem v rendereru, proto neescapovaný výpis. --}}
@if (filled($data['html'] ?? null))
    {!! $data['html'] !!}
@else
    <ul class="kb-checklist">
        @foreach ((array) ($data['items'] ?? []) as $entry)
            <li>{!! is_string($entry) ? $entry : ($entry['text'] ?? '') !!}</li>
        @endforeach
    </ul>
@endif
