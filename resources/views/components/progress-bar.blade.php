@props(['percentage', 'label'])

<div {{ $attributes }}>
    <div class="flex items-baseline justify-between gap-2 text-sm">
        <span class="font-medium text-ink">{{ $label }}</span>
        <span class="text-muted">{{ $percentage }}%</span>
    </div>
    <div class="mt-1.5 h-2.5 w-full overflow-hidden rounded-full bg-brand-100" role="progressbar"
        aria-label="{{ $label }}" aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
        <div class="h-full rounded-full bg-brand-gradient" style="width: {{ max(0, min(100, (int) $percentage)) }}%"></div>
    </div>
</div>
