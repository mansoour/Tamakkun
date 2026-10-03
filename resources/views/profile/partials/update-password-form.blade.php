<section aria-labelledby="update-password-title">
    <h2 id="update-password-title" class="text-lg font-semibold text-ink">تغيير كلمة المرور</h2>
    <p class="mt-1 text-sm text-muted">استخدمي كلمة مرور طويلة يصعب تخمينها للحفاظ على أمان حسابك.</p>

    @if (session('status') === 'password-updated')
        <x-alert type="success" class="mt-4">تم تحديث كلمة المرور.</x-alert>
    @endif

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="كلمة المرور الحالية" />
            <x-password-input id="update_password_current_password" name="current_password" autocomplete="current-password" dir="ltr" class="text-start" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="كلمة المرور الجديدة" />
            <x-password-input id="update_password_password" name="password" autocomplete="new-password" dir="ltr" class="text-start" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="تأكيد كلمة المرور الجديدة" />
            <x-password-input id="update_password_password_confirmation" name="password_confirmation" autocomplete="new-password" dir="ltr" class="text-start" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <x-primary-button>حفظ</x-primary-button>
    </form>
</section>
