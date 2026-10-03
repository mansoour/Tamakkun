@props(['name', 'label', 'checked' => false, 'hint' => null])

<div>
    <input type="hidden" name="{{ $name }}" value="0">
    <label for="{{ $name }}" class="inline-flex min-h-[44px] items-center gap-3">
        <input id="{{ $name }}" type="checkbox" name="{{ $name }}" value="1" @checked(old($name, $checked))
            class="h-5 w-5 rounded border-line text-brand-600 focus:ring-brand-600">
        <span class="text-sm font-medium text-ink">{{ $label }}</span>
    </label>
    @if ($hint)
        <p class="text-xs text-muted">{{ $hint }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" />
</div>
