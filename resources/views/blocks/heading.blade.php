@php
    $level = ($data['level'] ?? '2') === '3' ? 'h3' : 'h2';

    // Vlevo je výchozí, tak se nevypisuje – prázdný `style` by jen zbytečně
    // přebil to, co si stránka řekne sama.
    $align = in_array($data['align'] ?? '', ['center', 'right'], true)
        ? ' style="text-align: '.$data['align'].'"'
        : '';
@endphp
<{{ $level }}{!! $align !!}>{{ $data['text'] ?? '' }}</{{ $level }}>
