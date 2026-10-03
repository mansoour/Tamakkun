@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null])

@php
    $id = $attributes->get('id', $name);
    $selected = (string) old($name, $value);
@endphp

<div>
    <x-input-label :for="$id" :value="$label" />
    <select id="{{ $id }}" name="{{ $name }}"
        aria-invalid="{{ $errors->has($name) ? 'true' : 'false' }}"
        @if ($errors->has($name)) aria-describedby="{{ $id }}-error" @endif
        {{ $attributes->except('id')->merge(['class' => 'block w-full min-h-[44px] rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600']) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected($selected === (string) $optionValue)>{{ $optionLabel }}</option>
        @endforeach
        {{ $slot }}
    </select>
    @if ($hint)
        <p class="mt-1.5 text-xs text-muted">{{ $hint }}</p>
    @endif
    <x-input-error :messages="$errors->get($name)" id="{{ $id }}-error" />
</div>
