@props([
    'type' => 'info',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }} role="alert">
    @if ($message !== null)
        {{ $message }}
    @else
        {{ $slot }}
    @endif
</div>