<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        @include('layouts.partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen">
        <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:start-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2">تخطي إلى المحتوى</a>

        <div class="grid min-h-screen lg:grid-cols-2">
            <aside class="relative hidden overflow-hidden bg-brand-gradient p-12 text-white lg:flex lg:flex-col lg:justify-between">
                <a href="{{ route('home') }}" class="w-fit"><x-brand size="lg" inverted /></a>
                <div class="max-w-md space-y-4">
                    @if ($platformTagline)
                        <p class="font-heading text-3xl font-bold leading-relaxed">{{ $platformTagline }}</p>
                    @endif
                    <p class="text-lg text-white/85">استعداد • تدريب • متابعة • إنجاز</p>
                </div>
                <div class="space-y-4">
                    <x-illustration name="auth-side" class="max-h-72 w-auto" />
                    <p class="text-sm text-white/75">منصة لمتابعة استعداد طالبات المرحلة الثانوية لاختباري القدرات والتحصيلي.</p>
                    @if ($supervisorName)
                        <p class="inline-flex items-center gap-2 rounded-full bg-white/15 px-4 py-2 text-sm font-bold">
                            <x-icon name="check-badge" class="h-5 w-5" />
                            {{ $supervisorTitle }}: {{ $supervisorName }}
                        </p>
                    @endif
                </div>
            </aside>

            <main id="main" class="flex flex-col items-center justify-center px-4 py-10 sm:px-6">
                <a href="{{ route('home') }}" class="mb-8 lg:hidden"><x-brand size="lg" /></a>

                <div class="card w-full max-w-md p-6 sm:p-8">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
