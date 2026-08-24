<figure>
    <img src="{{ $data['src'] ?? '' }}" alt="{{ $data['alt'] ?? '' }}" loading="lazy" />
    @if (! empty($data['caption']))
        <figcaption>{{ $data['caption'] }}</figcaption>
    @endif
</figure>
