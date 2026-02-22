@props([
    'value' => null,
    'dash' => '-',
])

@if ($value === null || $value === '')
    {{ $dash }}
@else
    {{ number_format((int) $value, 0, ',', '.') }}
@endif