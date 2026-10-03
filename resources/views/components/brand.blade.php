@props(['size' => 'md', 'inverted' => false])

@php
    $box = $size === 'lg' ? 'h-12 w-12 rounded-2xl' : 'h-9 w-9 rounded-xl';
    $icon = $size === 'lg' ? 'h-7 w-7' : 'h-5 w-5';
    $text = $size === 'lg' ? 'text-3xl' : 'text-xl';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span @class([
        'inline-flex items-center justify-center',
        $box,
        'bg-white/20 text-white' => $inverted,
        'bg-brand-gradient text-white shadow-card' => ! $inverted,
    ])>
        <x-icon name="academic-cap" :class="$icon" />
    </span>
    <span @class(['font-heading font-bold', $text, 'text-white' => $inverted, 'text-ink' => ! $inverted])>تمكّن</span>
</span>
