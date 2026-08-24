<blockquote>
    <p>{{ $data['text'] ?? '' }}</p>
    @if (! empty($data['source']))
        <footer>{{ $data['source'] }}</footer>
    @endif
</blockquote>
