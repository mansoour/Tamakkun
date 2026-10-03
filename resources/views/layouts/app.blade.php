<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="min-h-screen" x-data="{ menuOpen: false }" x-on:keydown.escape.window="menuOpen = false">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">تخطي إلى المحتوى</a>

        {{-- Mobile / tablet top bar --}}
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-line bg-surface/95 px-4 backdrop-blur lg:hidden">
            <button type="button" class="btn-ghost -ms-2 px-3" x-on:click="menuOpen = true" aria-controls="app-drawer" :aria-expanded="menuOpen.toString()" aria-expanded="false">
                <x-icon name="bars-3" class="h-6 w-6" />
                <span class="sr-only">فتح القائمة</span>
            </button>
            <a href="{{ route('dashboard') }}"><x-brand /></a>
            <div class="-me-2 flex items-center">
                @if ($area === 'student')
                    @include('layouts.partials.notification-bell')
                @endif
                <a href="{{ route('profile.edit') }}" class="btn-ghost px-3">
                    <x-icon name="user-circle" class="h-6 w-6" />
                    <span class="sr-only">حسابي</span>
                </a>
            </div>
        </header>

        {{-- Mobile drawer --}}
        <div x-show="menuOpen" x-cloak class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="القائمة الرئيسية">
            <div class="absolute inset-0 bg-ink/40" x-on:click="menuOpen = false" x-show="menuOpen" x-transition.opacity></div>
            <div id="app-drawer" class="absolute inset-y-0 start-0 flex w-72 max-w-[85vw] flex-col bg-surface shadow-xl"
                x-show="menuOpen" x-trap.noscroll="menuOpen"
                x-transition:enter="transition duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                x-transition:leave="transition duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">
                <div class="flex h-16 items-center justify-between border-b border-line px-4">
                    <x-brand />
                    <button type="button" class="btn-ghost -me-2 px-3" x-on:click="menuOpen = false">
                        <x-icon name="x-mark" class="h-6 w-6" />
                        <span class="sr-only">إغلاق القائمة</span>
                    </button>
                </div>
                @include('layouts.partials.sidebar-nav')
            </div>
        </div>

        <div class="lg:flex">
            {{-- Desktop sidebar --}}
            <aside class="hidden lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-72 lg:shrink-0 lg:flex-col lg:border-e lg:border-line lg:bg-surface">
                <div class="flex h-20 items-center px-6">
                    <a href="{{ route('dashboard') }}"><x-brand /></a>
                </div>
                @include('layouts.partials.sidebar-nav')
            </aside>

            <div class="min-w-0 flex-1">
                @if ($area === 'student')
                    <div class="mx-auto hidden w-full max-w-6xl justify-end px-10 pt-6 lg:flex">
                        @include('layouts.partials.notification-bell')
                    </div>
                @endif
                <main id="main" class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
                    <x-flash />
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
