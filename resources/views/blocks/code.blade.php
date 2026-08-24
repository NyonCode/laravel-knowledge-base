@php $language = $data['language'] ?? null; @endphp
<pre class="kb-code"><code @if ($language) class="language-{{ $language }}" @endif>{{ $data['text'] ?? '' }}</code></pre>
