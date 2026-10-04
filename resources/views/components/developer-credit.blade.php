{{-- «تمكّن © 2026–current year · تطوير Mansoour» (Mansoour links to mansoour.com). The year appears once. --}}
@php
    $years = now()->year > 2026 ? '2026–'.now()->year : '2026';
    $name = app(\App\Services\SettingsService::class)->get('platform_name') ?: config('tamakkun.settings.platform_name');
@endphp
<p {{ $attributes->merge(['class' => 'text-xs']) }}>
    {{ $name }} © <span dir="ltr">{{ $years }}</span> ·
    تطوير <a href="https://mansoour.com" target="_blank" rel="noopener" class="font-bold hover:underline" dir="ltr">Mansoour</a>
</p>
