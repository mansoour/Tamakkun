@props(['name', 'label' => null])

@php
    $paths = config('icons.'.$name);

    if ($paths === null) {
        throw new InvalidArgumentException("Unknown icon [{$name}]. Add it to config/icons.php.");
    }
@endphp

<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif
    {{ $attributes->merge(['class' => 'h-5 w-5 shrink-0']) }}>
    @foreach ($paths as $d)
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $d }}" />
    @endforeach
</svg>
