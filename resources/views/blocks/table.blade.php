@php
    $lines = array_values(array_filter(
        array_map('trim', explode("\n", (string) ($data['rows'] ?? ''))),
        static fn (string $line): bool => $line !== '',
    ));
    $split = static fn (string $line): array => array_map('trim', explode('|', $line));
    $head = $lines === [] ? [] : $split(array_shift($lines));
@endphp
@if ($head !== [])
    <table>
        <thead>
            <tr>@foreach ($head as $cell)<th>{{ $cell }}</th>@endforeach</tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>@foreach ($split($line) as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @endforeach
        </tbody>
    </table>
@endif
