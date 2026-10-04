<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-ink">تعيين كلمة مرور جديدة</h1>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-form.input name="email" type="email" label="البريد الإلكتروني" :value="$request->email" required autofocus autocomplete="username" />
        <x-form.input name="password" type="password" label="كلمة المرور الجديدة" required autocomplete="new-password" />
        <x-form.input name="password_confirmation" type="password" label="تأكيد كلمة المرور" required autocomplete="new-password" />

        <x-primary-button class="w-full">حفظ كلمة المرور</x-primary-button>
    </form>
</x-guest-layout>
