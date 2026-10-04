<x-guest-layout title="إنشاء حساب طالبة">
    <h1 class="text-2xl font-extrabold text-ink">إنشاء حساب طالبة</h1>
    <p class="mt-1 text-sm text-muted">سجّلي بياناتك واختاري مدرستك وصفّك وفصلك، ثم ابدئي رحلتك مع القدرات والتحصيلي.</p>

    @if ($needsApproval)
        <x-alert type="info" class="mt-5">يُفعَّل الحساب بعد مراجعة المدرسة، ثم تستطيعين تسجيل الدخول.</x-alert>
    @endif

    @if ($schools === [])
        <x-empty-state class="mt-6" icon="building-library" title="التسجيل غير متاح حاليًا"
            description="لم تُضف المدارس والفصول بعد. تواصلي مع الموجهة الطلابية لإنشاء حسابك." />
    @else
        @php($selected = ['school' => old('school_id'), 'grade' => old('grade_id'), 'classroom' => old('classroom_id')])
        <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5" novalidate
            x-data="{
                schools: @js($schools),
                school: @js((string) $selected['school']),
                grade: @js((string) $selected['grade']),
                classroom: @js((string) $selected['classroom']),
                get grades() { return this.schools.find(s => String(s.id) === this.school)?.grades ?? [] },
                get classrooms() { return this.grades.find(g => String(g.id) === this.grade)?.classrooms ?? [] },
            }">
            @csrf

            <x-form.input name="name" label="الاسم الثلاثي" required autofocus autocomplete="name" maxlength="255" />

            <x-form.input name="username" label="اسم المستخدم" required autocomplete="username" maxlength="32"
                hint="بالحروف الإنجليزية أو الأرقام، وستستخدمينه لتسجيل الدخول. مثال: sara.ahmad" />

            <x-form.input name="email" type="email" label="البريد الإلكتروني" required autocomplete="email"
                hint="تستخدمينه لاستعادة كلمة المرور إن نسيتِها." />

            <fieldset class="space-y-4 rounded-2xl border border-line p-4">
                <legend class="px-1 text-sm font-bold text-ink">المدرسة والصف</legend>

                <div>
                    <x-input-label for="school_id" value="المدرسة" />
                    <select id="school_id" name="school_id" required x-model="school" x-on:change="grade = ''; classroom = ''"
                        aria-invalid="{{ $errors->has('school_id') ? 'true' : 'false' }}" aria-describedby="school_id-error"
                        class="block w-full min-h-[44px] rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600">
                        <option value="">اختاري المدرسة</option>
                        <template x-for="s in schools" :key="s.id">
                            <option :value="String(s.id)" x-text="s.name" :selected="String(s.id) === school"></option>
                        </template>
                    </select>
                    <x-input-error id="school_id-error" :messages="$errors->get('school_id')" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-input-label for="grade_id" value="الصف" />
                        <select id="grade_id" name="grade_id" required x-model="grade" x-on:change="classroom = ''" :disabled="! school"
                            aria-invalid="{{ $errors->has('grade_id') ? 'true' : 'false' }}" aria-describedby="grade_id-error"
                            class="block w-full min-h-[44px] rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600 disabled:bg-brand-50">
                            <option value="">اختاري الصف</option>
                            <template x-for="g in grades" :key="g.id">
                                <option :value="String(g.id)" x-text="g.name" :selected="String(g.id) === grade"></option>
                            </template>
                        </select>
                        <x-input-error id="grade_id-error" :messages="$errors->get('grade_id')" />
                    </div>

                    <div>
                        <x-input-label for="classroom_id" value="الفصل" />
                        <select id="classroom_id" name="classroom_id" required x-model="classroom" :disabled="! grade"
                            aria-invalid="{{ $errors->has('classroom_id') ? 'true' : 'false' }}" aria-describedby="classroom_id-error"
                            class="block w-full min-h-[44px] rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600 disabled:bg-brand-50">
                            <option value="">اختاري الفصل</option>
                            <template x-for="c in classrooms" :key="c.id">
                                <option :value="String(c.id)" x-text="c.name" :selected="String(c.id) === classroom"></option>
                            </template>
                        </select>
                        <x-input-error id="classroom_id-error" :messages="$errors->get('classroom_id')" />
                    </div>
                </div>
            </fieldset>

            <x-form.input name="password" type="password" label="كلمة المرور" required autocomplete="new-password" hint="8 أحرف على الأقل." />
            <x-form.input name="password_confirmation" type="password" label="تأكيد كلمة المرور" required autocomplete="new-password" />

            <x-primary-button class="w-full">
                <x-icon name="user-plus" />
                إنشاء الحساب
            </x-primary-button>
        </form>
    @endif

    <p class="mt-6 text-center text-sm text-muted">
        لديكِ حساب؟
        <a href="{{ route('login') }}" class="font-bold text-brand-700 underline-offset-4 hover:underline">تسجيل الدخول</a>
    </p>
</x-guest-layout>
