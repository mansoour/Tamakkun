{{--
    A site graphic from public/images/illustrations/{name}.(webp|png|svg|jpg).
    The files are listed in docs/graphics.md. Until a file is added, the
    optional `icon` fallback is shown (a soft brand tile); without one,
    nothing is rendered. Graphics are decorative unless `alt` is given.
--}}
@props(['name', 'alt' => '', 'icon' => null, 'eager' => false])

@php
    $file = collect(['webp', 'png', 'svg', 'jpg'])
        ->map(fn ($ext) => "images/illustrations/{$name}.{$ext}")
        ->first(fn ($path) => is_file(public_path($path)));
@endphp

@if ($file)
    <img src="{{ asset($file) }}?v={{ filemtime(public_path($file)) }}" alt="{{ $alt }}" @if ($alt === '') aria-hidden="true" @endif
        loading="{{ $eager ? 'eager' : 'lazy' }}" decoding="async" {{ $attributes->merge(['class' => 'object-contain']) }}>
@elseif ($icon)
    <div aria-hidden="true" {{ $attributes->merge(['class' => 'flex items-center justify-center rounded-3xl bg-gradient-to-br from-brand-100 via-brand-50 to-white']) }}>
        <span class="inline-flex h-20 w-20 items-center justify-center rounded-3xl bg-brand-gradient text-white shadow-card">
            <x-icon :name="$icon" class="h-10 w-10" />
        </span>
    </div>
@endif
