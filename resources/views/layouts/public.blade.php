<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        @include('layouts.partials.head', ['title' => $title])
    </head>
    <body class="flex min-h-screen flex-col">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">تخطي إلى المحتوى</a>

        <header class="sticky top-0 z-30 border-b border-line/70 bg-canvas/90 backdrop-blur" x-data="{ open: false }" x-on:keydown.escape.window="open = false">
            <div class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
                <a href="{{ route('home') }}" aria-label="الصفحة الرئيسية"><x-brand /></a>

                <nav class="hidden items-center gap-1 lg:flex" aria-label="القائمة الرئيسية">
                    @foreach ($navLinks as $link)
                        @php($active = request()->routeIs($link['active'] ?? $link['route']))
                        <a href="{{ route($link['route']) }}" @if ($active) aria-current="page" @endif
                            @class(['rounded-lg px-3 py-2 text-sm font-bold transition', 'bg-brand-100 text-brand-800' => $active, 'text-muted hover:bg-brand-50 hover:text-brand-800' => ! $active])>{{ $link['label'] }}</a>
                    @endforeach
                </nav>

                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary">لوحتي</a>
                    @else
                        @if ($registrationOpen)
                            <a href="{{ route('register') }}" class="btn-primary hidden sm:inline-flex">إنشاء حساب</a>
                        @endif
                        <a href="{{ route('login') }}" class="btn-secondary">تسجيل الدخول</a>
                    @endauth
                    <button type="button" class="btn-ghost -me-2 px-3 lg:hidden" x-on:click="open = ! open" aria-controls="public-menu" :aria-expanded="open.toString()" aria-expanded="false">
                        <x-icon name="bars-3" class="h-6 w-6" x-show="! open" />
                        <x-icon name="x-mark" class="h-6 w-6" x-show="open" x-cloak />
                        <span class="sr-only">القائمة</span>
                    </button>
                </div>
            </div>

            <nav id="public-menu" x-show="open" x-cloak x-transition.opacity class="border-t border-line bg-surface lg:hidden" aria-label="القائمة الرئيسية">
                <ul class="mx-auto max-w-6xl space-y-1 px-4 py-3">
                    @foreach ($navLinks as $link)
                        @php($active = request()->routeIs($link['active'] ?? $link['route']))
                        <li>
                            <a href="{{ route($link['route']) }}" @if ($active) aria-current="page" @endif
                                @class(['flex min-h-[44px] items-center gap-3 rounded-xl px-3 text-sm font-bold', 'bg-brand-100 text-brand-800' => $active, 'text-ink hover:bg-brand-50' => ! $active])>
                                <x-icon :name="$link['icon']" class="h-5 w-5" /> {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                    @guest
                        @if ($registrationOpen)
                            <li class="pt-2 sm:hidden"><a href="{{ route('register') }}" class="btn-primary w-full"><x-icon name="user-plus" /> إنشاء حساب طالبة</a></li>
                        @endif
                    @endguest
                </ul>
            </nav>
        </header>

        <main id="main" class="mx-auto w-full max-w-6xl flex-1 px-4 pb-16 pt-6 sm:px-6">
            {{ $slot }}
        </main>

        <footer class="border-t border-line py-6 text-sm text-muted">
            <div class="mx-auto flex max-w-6xl flex-col items-center gap-3 px-4 sm:flex-row sm:justify-between sm:px-6">
                <nav aria-label="روابط المنصة">
                    <ul class="flex flex-wrap justify-center gap-x-5 gap-y-2">
                        @foreach ($footerLinks as $link)
                            <li><a href="{{ route($link['route']) }}" class="hover:text-brand-700 hover:underline">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </nav>
                <div class="text-center sm:text-end">
                    <x-developer-credit class="text-sm text-muted" />
                    @if ($supervisorName)
                        <p class="mt-1 text-xs">{{ $supervisorTitle }}: <span class="font-bold text-ink">{{ $supervisorName }}</span></p>
                    @endif
                </div>
            </div>
        </footer>
    </body>
</html>
