<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-ink">تأكيد كلمة المرور</h1>
    <p class="mt-2 text-sm text-muted">هذه منطقة محمية. يرجى تأكيد كلمة المرور للمتابعة.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
        @csrf

        <x-form.input name="password" type="password" label="كلمة المرور" required autocomplete="current-password" dir="ltr" class="text-start" />

        <x-primary-button class="w-full">تأكيد</x-primary-button>
    </form>
</x-guest-layout>
