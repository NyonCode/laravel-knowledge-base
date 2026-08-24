<ol>
    @foreach ((array) ($data['items'] ?? []) as $item)
        <li>{{ is_string($item) ? $item : ($item['text'] ?? '') }}</li>
    @endforeach
</ol>
