<x-guest-layout>
    <div x-data="{ audience: @js(request('as') === 'counselor' ? 'counselor' : 'student') }">
        <h1 class="text-2xl font-extrabold text-ink">تسجيل الدخول</h1>
        <p class="mt-1 text-sm text-muted">أهلًا بكِ في تمكّن. اختاري نوع الحساب ثم أدخلي بياناتك.</p>

        <div class="mt-6 grid grid-cols-2 gap-1 rounded-xl bg-brand-50 p-1" role="radiogroup" aria-label="نوع الحساب">
            <button type="button" role="radio" x-on:click="audience = 'student'" :aria-checked="(audience === 'student').toString()"
                :class="audience === 'student' ? 'bg-surface text-brand-800 shadow-sm' : 'text-muted'"
                class="min-h-[44px] rounded-lg px-2 text-[13px] font-bold transition sm:text-sm">دخول الطالبة</button>
            <button type="button" role="radio" x-on:click="audience = 'counselor'" :aria-checked="(audience === 'counselor').toString()"
                :class="audience === 'counselor' ? 'bg-surface text-brand-800 shadow-sm' : 'text-muted'"
                class="min-h-[44px] rounded-lg px-2 text-[13px] font-bold transition sm:text-sm">دخول الموجهة الطلابية</button>
        </div>

        <x-auth-session-status class="mt-6" :status="session('status')" />

        <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
            @csrf

            <div>
                <x-input-label for="login">
                    <span x-text="audience === 'student' ? 'اسم المستخدم أو رقم الطالبة' : 'البريد الإلكتروني أو اسم المستخدم'">اسم المستخدم أو البريد الإلكتروني</span>
                </x-input-label>
                <x-text-input id="login" name="login" type="text" :value="old('login')" required autofocus
                    autocomplete="username" dir="ltr" class="text-start"
                    :aria-invalid="$errors->has('login') ? 'true' : 'false'" aria-describedby="login-error" />
                <x-input-error id="login-error" :messages="$errors->get('login')" />
            </div>

            <div>
                <x-input-label for="password" value="كلمة المرور" />
                <x-password-input id="password" name="password" required autocomplete="current-password" dir="ltr" class="text-start" />
                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3">
                <label for="remember_me" class="inline-flex min-h-[44px] items-center gap-2">
                    <input id="remember_me" type="checkbox" class="h-5 w-5 rounded border-line text-brand-600 focus:ring-brand-600" name="remember">
                    <span class="text-sm text-ink">تذكّريني</span>
                </label>

                <a class="text-sm font-medium text-brand-700 underline-offset-4 hover:underline" href="{{ route('password.request') }}">
                    نسيتِ كلمة المرور؟
                </a>
            </div>

            <x-primary-button class="w-full">
                <x-icon name="arrow-left-end-on-rectangle" />
                دخول
            </x-primary-button>
        </form>

        <p class="mt-6 text-center text-xs text-muted">
            الحسابات تُنشأ من قِبل المدرسة. إن لم يكن لديك حساب فتواصلي مع الموجهة الطلابية.
        </p>
    </div>
</x-guest-layout>
