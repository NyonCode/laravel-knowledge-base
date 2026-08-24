{{-- Blok uložený dnešním editorem nese hotové HTML; starší tvar (řádek na
     položku) se pořád vykreslí, protože se převádí až při editaci. Obojí
     projde sanitizérem v rendereru, proto neescapovaný výpis. --}}
@if (filled($data['html'] ?? null))
    {!! $data['html'] !!}
@else
    <ol>
        @foreach ((array) ($data['items'] ?? []) as $entry)
            <li>{!! is_string($entry) ? $entry : ($entry['text'] ?? '') !!}</li>
        @endforeach
    </ol>
@endif
