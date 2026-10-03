<div class="flex min-h-0 flex-1 flex-col">
    <p class="px-6 pb-2 pt-4 text-xs font-semibold text-muted">{{ $areaTitle }}</p>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 pb-4" aria-label="{{ $areaTitle }}">
        @foreach ($navigation as $item)
            @if ($item['route'])
                @php($active = request()->routeIs($item['active'] ?? $item['route']))
                <a href="{{ route($item['route']) }}" @if ($active) aria-current="page" @endif
                    @class([
                        'flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-medium transition',
                        'bg-brand-100 text-brand-800' => $active,
                        'text-ink hover:bg-brand-50' => ! $active,
                    ])>
                    <x-icon :name="$item['icon']" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @else
                <div class="flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm text-muted" aria-disabled="true">
                    <x-icon :name="$item['icon']" class="h-5 w-5 opacity-60" />
                    <span>{{ $item['label'] }}</span>
                    <x-badge color="gray" class="ms-auto">قريبًا</x-badge>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="border-t border-line p-4">
        <p class="truncate text-sm font-semibold text-ink">{{ Auth::user()->name }}</p>
        <p class="truncate text-xs text-muted" dir="ltr">{{ Auth::user()->username }}</p>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn-secondary w-full">
                <x-icon name="arrow-right-start-on-rectangle" />
                تسجيل الخروج
            </button>
        </form>
    </div>
</div>
