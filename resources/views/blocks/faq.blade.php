{{-- `details` místo vlastního rozbalovátka: funguje bez JavaScriptu, umí ho
     prohlížečové hledání i tisk. --}}
<details class="kb-faq">
    <summary>{{ $data['question'] ?? '' }}</summary>
    {!! \NyonCode\KnowledgeBase\Support\Html::prose($data['text'] ?? '') !!}
</details>
