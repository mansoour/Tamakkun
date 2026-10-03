<x-app-layout :area="$area" title="الإعلانات">
    <x-page-header title="الإعلانات" description="تظهر في لوحة الطالبة ويُرسل إشعار عند بدء عرضها.">
        <x-slot:actions>
            <a href="{{ route($area.'.announcements.create') }}" class="btn-primary"><x-icon name="megaphone" /> إعلان جديد</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 space-y-3">
        @forelse ($announcements as $announcement)
            <article class="card flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-heading font-bold text-ink">{{ $announcement->title }}</h2>
                        <x-badge>{{ $announcement->audience->label() }}</x-badge>
                        @if (! $announcement->is_published)
                            <x-badge color="gray">مسحوب</x-badge>
                        @elseif ($announcement->starts_at?->isFuture())
                            <x-badge color="warning">مجدول</x-badge>
                        @endif
                    </div>
                    <p class="mt-1 line-clamp-2 text-sm text-muted">{{ $announcement->body }}</p>
                    <p class="mt-1 text-xs text-muted">
                        {{ $announcement->author?->name }} ·
                        <span dir="ltr">{{ ($announcement->starts_at ?? $announcement->created_at)->format('Y-m-d H:i') }}</span>
                        @if ($announcement->ends_at) → <span dir="ltr">{{ $announcement->ends_at->format('Y-m-d') }}</span> @endif
                    </p>
                </div>
                @if ($announcement->is_published)
                    <form method="POST" action="{{ route($area.'.announcements.withdraw', $announcement) }}">
                        @csrf
                        <button class="btn-ghost min-h-[40px] px-3">سحب</button>
                    </form>
                @endif
            </article>
        @empty
            <x-empty-state icon="megaphone" title="لا توجد إعلانات" />
        @endforelse
        {{ $announcements->links() }}
    </div>
</x-app-layout>
