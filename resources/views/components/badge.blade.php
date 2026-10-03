@props(['color' => 'brand'])

@php
    $classes = [
        'brand' => 'bg-brand-100 text-brand-800',
        'gray' => 'bg-gray-100 text-gray-700',
        'success' => 'bg-emerald-100 text-emerald-800',
        'warning' => 'bg-amber-100 text-amber-900',
        'danger' => 'bg-red-100 text-red-800',
    ][$color] ?? 'bg-brand-100 text-brand-800';
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {$classes}"]) }}>
    {{ $slot }}
</span>
