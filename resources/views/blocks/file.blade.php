@if (! empty($data['url']))
    <p class="kb-file">
        <a href="{{ $data['url'] }}" download>{{ $data['label'] ?? $data['url'] }}</a>
    </p>
@endif
