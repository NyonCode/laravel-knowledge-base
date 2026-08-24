@php
    $url = (string) ($data['url'] ?? '');
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    $allowed = in_array($host, array_map('strtolower',
        \NyonCode\KnowledgeBase\Support\Settings::strings('editors.blocks.embed_hosts')), true);

    // Adresa se překládá na embed tvar tady, ne v editoru: autor vloží, co má
    // v adresním řádku, a hádat se s ním o formát URL je práce pro kód.
    $embed = null;
    if ($allowed) {
        if (str_contains($host, 'youtu')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
            $id = $query['v'] ?? trim((string) parse_url($url, PHP_URL_PATH), '/');
            $embed = $id ? 'https://www.youtube.com/embed/'.$id : null;
        } elseif (str_contains($host, 'vimeo')) {
            $id = trim((string) parse_url($url, PHP_URL_PATH), '/');
            $embed = $id ? 'https://player.vimeo.com/video/'.$id : null;
        }
    }
@endphp
@if ($embed)
    <figure class="kb-video">
        <iframe src="{{ $embed }}" title="{{ $data['title'] ?? 'video' }}" allowfullscreen loading="lazy"></iframe>
    </figure>
@elseif ($url !== '')
    {{-- Nepovolený zdroj se nezahodí mlčky: odkaz zůstane, aby o obsah nikdo
         nepřišel, a je vidět, že přehrávač chybí schválně. --}}
    <p class="kb-video-fallback"><a href="{{ $url }}" rel="noopener">{{ $url }}</a></p>
@endif
