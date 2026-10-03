@props(['icon' => 'information-circle', 'title', 'description' => null])

<div {{ $attributes->merge(['class' => 'card flex flex-col items-center px-6 py-12 text-center']) }}>
    <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-100 text-brand-700">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <p class="mt-4 font-heading font-bold text-ink">{{ $title }}</p>
    @if ($description)
        <p class="mt-1 max-w-md text-sm text-muted">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
