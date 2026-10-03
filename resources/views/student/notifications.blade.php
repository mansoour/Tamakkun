<x-app-layout area="student" title="الإشعارات">
    <x-page-header title="الإشعارات">
        @if ($notifications->contains(fn ($n) => $n->read_at === null))
            <x-slot:actions>
                <form method="POST" action="{{ route('student.notifications.read') }}">
                    @csrf
                    <button class="btn-secondary">تعليم الكل كمقروء</button>
                </form>
            </x-slot:actions>
        @endif
    </x-page-header>

    @if ($notifications->isEmpty())
        <x-empty-state class="mt-6" icon="bell" title="لا توجد إشعارات" />
    @else
        <ul class="card mt-6 divide-y divide-line">
            @foreach ($notifications as $notification)
                <li>
                    <a href="{{ route('student.notifications.open', $notification->id) }}" @class(['flex items-start gap-3 p-4 transition hover:bg-brand-50', 'bg-brand-50/60' => $notification->read_at === null])>
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-100 text-brand-700">
                            <x-icon :name="$notification->data['icon'] ?? 'bell'" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-2">
                                <span class="font-heading text-sm font-semibold text-ink">{{ $notification->data['title'] ?? '' }}</span>
                                @if ($notification->read_at === null) <x-badge color="brand">جديد</x-badge> @endif
                            </span>
                            <span class="mt-1 block text-sm text-muted">{{ $notification->data['body'] ?? '' }}</span>
                            <span class="mt-1 block text-xs text-muted" dir="ltr">{{ $notification->created_at->format('Y-m-d H:i') }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
        {{ $notifications->links() }}
    @endif
</x-app-layout>
