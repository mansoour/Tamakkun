@props(['source'])

@if ($source)
    <x-badge color="gray" {{ $attributes }}>
        <x-icon name="building-library" class="h-3.5 w-3.5" />
        <span><span class="sr-only">المصدر: </span>{{ $source->name }}</span>
    </x-badge>
@endif
