@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])

@php
    $id = $attributes->get('id', $name);
    $describedBy = trim(($hint ? "{$id}-hint " : '').($errors->has($name) ? "{$id}-error" : ''));
@endphp

<div>
    <x-input-label :for="$id" :value="$label" />

    @if ($type === 'password')
        <x-password-input :id="$id" :name="$name" :aria-invalid="$errors->has($name) ? 'true' : 'false'"
            :aria-describedby="$describedBy ?: null" {{ $attributes->except('id') }} />
    @else
        <x-text-input :id="$id" :name="$name" :type="$type" :value="old($name, $value)"
            :aria-invalid="$errors->has($name) ? 'true' : 'false'" :aria-describedby="$describedBy ?: null"
            {{ $attributes->except('id') }} />
    @endif

    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
    @endif

    <x-input-error :messages="$errors->get($name)" id="{{ $id }}-error" />
</div>
