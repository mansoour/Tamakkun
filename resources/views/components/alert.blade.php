@props(['type' => 'info', 'title' => null])

@php
    $styles = [
        'info' => ['bg-brand-50 border-brand-200 text-brand-900', 'information-circle'],
        'success' => ['bg-emerald-50 border-emerald-200 text-emerald-900', 'check-circle'],
        'warning' => ['bg-amber-50 border-amber-200 text-amber-900', 'exclamation-triangle'],
        'danger' => ['bg-red-50 border-red-200 text-red-900', 'x-circle'],
    ];
    [$classes, $icon] = $styles[$type] ?? $styles['info'];
@endphp

<div {{ $attributes->merge(['class' => "flex gap-3 rounded-xl border p-4 text-sm {$classes}"]) }} role="{{ in_array($type, ['warning', 'danger']) ? 'alert' : 'status' }}">
    <x-icon :name="$icon" class="mt-0.5 h-5 w-5" />
    <div class="space-y-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div>{{ $slot }}</div>
    </div>
</div>
