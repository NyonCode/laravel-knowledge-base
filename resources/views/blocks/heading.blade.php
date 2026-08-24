@php $level = ($data['level'] ?? '2') === '3' ? 'h3' : 'h2'; @endphp
<{{ $level }}>{{ $data['text'] ?? '' }}</{{ $level }}>
