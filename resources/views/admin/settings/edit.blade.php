<x-app-layout area="admin" title="الإعدادات">
    <x-page-header title="الإعدادات" description="تُحفظ كل التغييرات في سجل التدقيق." />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="card mt-6 max-w-2xl space-y-6 p-6">
        @csrf
        @method('PUT')

        <section aria-labelledby="security-title" class="space-y-3">
            <h2 id="security-title" class="font-heading text-lg font-semibold text-ink">الأمان</h2>
            <x-form.checkbox name="force_password_change" label="إلزام تغيير كلمة المرور عند أول دخول"
                :checked="$settings['force_password_change']"
                hint="عند التفعيل: كل حساب حدّدت الإدارة كلمة مروره (عند الإنشاء أو الاستيراد أو إعادة التعيين) يُطلب منه اختيار كلمة مرور جديدة قبل استخدام المنصة." />
        </section>

        <section aria-labelledby="progress-title" class="space-y-3 border-t border-line pt-6">
            <h2 id="progress-title" class="font-heading text-lg font-semibold text-ink">التقدّم</h2>
            <x-form.input name="weekly_content_goal" type="number" min="1" max="50" label="هدف الإنجاز الأسبوعي (عدد الدروس والمقاطع)"
                :value="$settings['weekly_content_goal']" hint="يُستخدم لحساب نسبة الأسبوع في لوحة الطالبة وصفحة تقدّمي." />
            <x-form.checkbox name="enable_gamification" label="تفعيل الأوسمة" :checked="$settings['enable_gamification']"
                hint="أوسمة شخصية إيجابية تظهر للطالبة في صفحة تقدّمي فقط. لا يوجد ترتيب عام بين الطالبات." />
        </section>

        <x-primary-button>حفظ الإعدادات</x-primary-button>
    </form>
</x-app-layout>
