{{-- Statická odškrtávátka: stav se nikam neukládá a nemá — návod čte deset
     lidí a zaškrtnutí jednoho z nich není pravda pro ostatní. --}}
<ul class="kb-checklist">
    @foreach ((array) ($data['items'] ?? []) as $item)
        <li>{{ is_string($item) ? $item : ($item['text'] ?? '') }}</li>
    @endforeach
</ul>
