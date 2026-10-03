<x-guest-layout>
    <h1 class="text-2xl font-bold text-ink">تأكيد البريد الإلكتروني</h1>
    <p class="mt-2 text-sm leading-relaxed text-muted">
        أرسلنا رابط تأكيد إلى بريدك الإلكتروني. إن لم يصلك الرابط يمكنك طلب رابط جديد.
    </p>

    @if (session('status') == 'verification-link-sent')
        <x-alert type="success" class="mt-6">تم إرسال رابط تأكيد جديد إلى بريدك الإلكتروني.</x-alert>
    @endif

    <div class="mt-6 flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full">إعادة إرسال رابط التأكيد</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-ghost w-full">تسجيل الخروج</button>
        </form>
    </div>
</x-guest-layout>
