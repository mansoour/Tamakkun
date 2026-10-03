@props(['label', 'value', 'icon' => 'chart-bar', 'hint' => null])

<div class="card flex items-start gap-4 p-5">
    <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <div>
        <p class="text-sm text-muted">{{ $label }}</p>
        <p class="mt-1 font-heading text-2xl font-bold text-ink">{{ $value }}</p>
        @if ($hint)
            <p class="mt-1 text-xs text-muted">{{ $hint }}</p>
        @endif
    </div>
</div>
