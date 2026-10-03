@props(['label' => null])

{{-- Horizontally scrollable on small screens so tables never break the layout. --}}
<div {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    {{-- Focusable so keyboard users can scroll wide tables (WCAG 2.1.1). --}}
    <div class="relative overflow-x-auto" tabindex="0" @if ($label) role="region" aria-label="{{ $label }}" @endif>
        <table class="min-w-full divide-y divide-line text-sm">
            @isset($head)
                <thead class="bg-brand-50 text-start text-xs font-semibold text-muted">
                    <tr>{{ $head }}</tr>
                </thead>
            @endisset
            <tbody class="divide-y divide-line">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
