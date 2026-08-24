<dl class="kb-definition">
    <dt>{{ $data['term'] ?? '' }}</dt>
    <dd>{!! \NyonCode\KnowledgeBase\Support\Html::prose($data['text'] ?? '') !!}</dd>
</dl>
