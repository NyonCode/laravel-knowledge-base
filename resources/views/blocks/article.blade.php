@php
    $slug = (string) ($data['slug'] ?? '');
    $article = $slug === ''
        ? null
        : \NyonCode\KnowledgeBase\Models\Article::query()->where('slug', $slug)->first();
@endphp
@if ($article)
    {{-- Titulek se čte z článku, neukládá se do bloku: přejmenovaný článek by
         jinak měl v deseti odkazech staré jméno. --}}
    <p class="kb-article-link">
        <a href="{{ \NyonCode\KnowledgeBase\Support\Routes::article($article) }}">{{ $article->title }}</a>
        @if ($article->excerpt)
            <span class="kb-article-link-excerpt">{{ $article->excerpt }}</span>
        @endif
    </p>
@endif
