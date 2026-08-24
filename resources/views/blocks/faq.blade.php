{{-- `details` místo vlastního rozbalovátka: funguje bez JavaScriptu, umí ho
     prohlížečové hledání i tisk. --}}
<details class="kb-faq">
    <summary>{{ $data['question'] ?? '' }}</summary>
    <p>{{ $data['text'] ?? '' }}</p>
</details>
