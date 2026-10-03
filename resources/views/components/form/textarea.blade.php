@props(['name', 'label', 'value' => null, 'hint' => null, 'rows' => 4])

@php
    $id = $attributes->get('id', $name);
    $describedBy = trim(($hint ? "{$id}-hint " : '').($errors->has($name) ? "{$id}-error" : ''));
@endphp

<div>
    <x-input-label :for="$id" :value="$label" />
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('id')->merge(['class' => 'mt-1 block w-full rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600']) }}>{{ old($name, $value) }}</textarea>

    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$errors->get($name)" id="{{ $id }}-error" />
</div>
