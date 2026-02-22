@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
    'required' => false,
])

<div class="form-group">
    @if ($label !== null)
        <label for="{{ $name }}">{{ $label }}</label>
    @endif

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $value }}"
        @if ($required) required @endif
        {{ $attributes->merge(['class' => 'form-control']) }}
    >
</div>