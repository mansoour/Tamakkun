<x-guest-layout>
    <h1 class="text-2xl font-bold text-ink">استعادة كلمة المرور</h1>
    <p class="mt-2 text-sm leading-relaxed text-muted">
        أدخلي البريد الإلكتروني المسجّل في حسابك وسنرسل لك رابطًا لتعيين كلمة مرور جديدة.
        إن لم يكن لحسابك بريد إلكتروني فتواصلي مع الموجهة الطلابية لإعادة تعيين كلمة المرور.
    </p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <x-form.input name="email" type="email" label="البريد الإلكتروني" required autofocus autocomplete="email" dir="ltr" class="text-start" />

        <x-primary-button class="w-full">إرسال رابط الاستعادة</x-primary-button>

        <a href="{{ route('login') }}" class="btn-ghost w-full">العودة إلى تسجيل الدخول</a>
    </form>
</x-guest-layout>
