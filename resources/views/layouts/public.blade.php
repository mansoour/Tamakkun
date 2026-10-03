<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        @include('layouts.partials.head', ['title' => $title])
    </head>
    <body class="flex min-h-screen flex-col">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">تخطي إلى المحتوى</a>

        <header class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-4 py-5 sm:px-6">
            <a href="{{ route('home') }}" aria-label="الصفحة الرئيسية"><x-brand /></a>
            @auth
                <a href="{{ route('dashboard') }}" class="btn-primary">لوحتي</a>
            @else
                <a href="{{ route('login') }}" class="btn-secondary">تسجيل الدخول</a>
            @endauth
        </header>

        <main id="main" class="mx-auto w-full max-w-6xl flex-1 px-4 pb-16 sm:px-6">
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
                <p>{{ $platformName }} © {{ now()->year }}</p>
            </div>
        </footer>
    </body>
</html>
