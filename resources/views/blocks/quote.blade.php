<blockquote>
    {!! \NyonCode\KnowledgeBase\Support\Html::prose($data['text'] ?? '') !!}
    @if (! empty($data['source']))
        <footer>{{ $data['source'] }}</footer>
    @endif
</blockquote>
