<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-ink">تغيير كلمة المرور</h1>
    <p class="mt-2 text-sm leading-relaxed text-muted">
        كلمة المرور الحالية حدّدتها إدارة المدرسة. لحماية حسابك اختاري كلمة مرور جديدة قبل المتابعة.
    </p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <x-input-label for="current_password" value="كلمة المرور الحالية" />
            <x-password-input id="current_password" name="current_password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>
        <div>
            <x-input-label for="password" value="كلمة المرور الجديدة" />
            <x-password-input id="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>
        <div>
            <x-input-label for="password_confirmation" value="تأكيد كلمة المرور الجديدة" />
            <x-password-input id="password_confirmation" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-primary-button class="w-full">حفظ كلمة المرور والمتابعة</x-primary-button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3">
        @csrf
        <button type="submit" class="btn-ghost w-full">تسجيل الخروج</button>
    </form>
</x-guest-layout>
