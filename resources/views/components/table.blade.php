{{-- Horizontally scrollable on small screens so tables never break the layout. --}}
<div {{ $attributes->merge(['class' => 'card overflow-hidden']) }}>
    <div class="relative overflow-x-auto">
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
