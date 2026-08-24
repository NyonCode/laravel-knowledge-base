@php
    $tones = [
        'info' => 'border-sky-300 bg-sky-50',
        'success' => 'border-emerald-300 bg-emerald-50',
        'warning' => 'border-amber-300 bg-amber-50',
        'danger' => 'border-rose-300 bg-rose-50',
    ];
    $tone = $tones[$data['tone'] ?? 'info'] ?? $tones['info'];
@endphp
{{-- Tón nese třída, ne inline styl: článek se čte i v tmavém režimu a
     napevno zapsaná barva by tam svítila. --}}
<div class="kb-callout rounded-xl border p-4 {{ $tone }}">
    @if (! empty($data['title']))
        <p class="font-semibold">{{ $data['title'] }}</p>
    @endif
    {!! \NyonCode\KnowledgeBase\Support\Html::prose($data['text'] ?? '') !!}
</div>
