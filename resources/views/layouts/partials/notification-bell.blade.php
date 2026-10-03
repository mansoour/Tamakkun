<a href="{{ route('student.notifications') }}" class="btn-ghost relative px-3">
    <x-icon name="bell" class="h-6 w-6" />
    @if ($unreadNotifications > 0)
        <span class="absolute end-1.5 top-1.5 inline-flex min-w-[1.25rem] items-center justify-center rounded-full bg-red-600 px-1 text-[11px] font-bold text-white">
            {{ $unreadNotifications > 9 ? '9+' : $unreadNotifications }}
        </span>
    @endif
    <span class="sr-only">الإشعارات{{ $unreadNotifications ? ' ('.$unreadNotifications.' غير مقروءة)' : '' }}</span>
</a>
