{{--
    One lesson in the public catalogue. Guests see the title, type and stage
    only (no URL); the item leads to sign-up or login. Students open it.
--}}
@props(['content'])

@php
    $user = auth()->user();
    $href = match (true) {
        (bool) $user?->can(\App\Enums\PermissionName::ACCESS_STUDENT_AREA->value) => route('student.content.show', $content),
        $user !== null => null,
        default => app(\App\Services\StudentRegistrationService::class)->isOpen() ? route('register') : route('login'),
    };
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif class="flex items-center gap-3 rounded-xl border border-line bg-surface px-3 py-2.5 text-sm transition hover:border-brand-300">
    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-brand-700">
        <x-icon :name="$content->content_type->icon()" class="h-5 w-5" />
    </span>
    <span class="min-w-0 flex-1">
        <span class="block truncate font-medium text-ink">{{ $content->title }}</span>
        <span class="text-xs text-muted">
            {{ $content->content_type->label() }}@if ($content->stage) · {{ $content->stage->label() }}@endif @if ($content->durationMinutes()) · {{ $content->durationMinutes() }} د @endif
        </span>
    </span>
    @guest
        <x-icon name="lock-closed" class="h-4 w-4 shrink-0 text-muted" />
        <span class="sr-only">يتطلب تسجيل الدخول</span>
    @endguest
</{{ $tag }}>
