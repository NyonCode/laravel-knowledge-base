{{-- The one block worth having: "read this or lose an hour". --}}
<div class="kb-callout rounded-xl border border-amber-300 bg-amber-50 p-4">
    @if (! empty($data['title']))
        <p class="font-semibold">{{ $data['title'] }}</p>
    @endif
    <p>{{ $data['text'] ?? '' }}</p>
</div>
