<x-app-layout area="admin" title="الإعدادات">
    <x-page-header title="الإعدادات" description="تُحفظ كل التغييرات في سجل التدقيق." />

    <form method="POST" action="{{ route('admin.settings.update') }}" class="card mt-6 max-w-3xl space-y-6 p-6">
        @csrf
        @method('PUT')

        <section aria-labelledby="general-title" class="space-y-4">
            <h2 id="general-title" class="font-heading text-lg font-bold text-ink">عام</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="platform_name" label="اسم المنصة" maxlength="40" required :value="$settings['platform_name']"
                    hint="يظهر في الشعار وعنوان الصفحات." />
                <x-form.input name="tagline" label="العبارة التعريفية" maxlength="120" :value="$settings['tagline']"
                    hint="تظهر في الصفحة الرئيسية وصفحة الدخول." />
                <x-form.input name="supervisor_name" label="اسم المشرفة" maxlength="80" :value="$settings['supervisor_name']"
                    hint="يظهر في الصفحة الرئيسية وصفحة الدخول وتذييل الموقع." />
                <x-form.input name="supervisor_title" label="صفة الإشراف" maxlength="80" :value="$settings['supervisor_title']"
                    hint="مثال: إشراف وإدارة المنصة." />
            </div>
        </section>

        <section aria-labelledby="registration-title" class="space-y-4 border-t border-line pt-6">
            <h2 id="registration-title" class="font-heading text-lg font-bold text-ink">تسجيل الطالبات</h2>
            <x-form.select name="student_registration" label="إنشاء الطالبة حسابها بنفسها" :value="$settings['student_registration']"
                :options="['open' => 'مفتوح: يُفعَّل الحساب فورًا', 'approval' => 'مفتوح بموافقة: تفعّل الإدارة الحساب', 'closed' => 'مغلق: الحسابات تُنشأ من المدرسة فقط']"
                hint="تختار الطالبة مدرستها وصفّها وفصلها من القوائم. تظهر المدارس النشطة ذات العام الدراسي الحالي فقط." />
            <x-form.checkbox name="show_leaderboard" label="إظهار «لوحة الشرف» للزوار" :checked="$settings['show_leaderboard']"
                hint="تعرض الاسم الأول والحرف الأول من اسم العائلة والصف فقط، دون المدرسة أو اسم المستخدم." />
        </section>

        <section aria-labelledby="security-title" class="space-y-3 border-t border-line pt-6">
            <h2 id="security-title" class="font-heading text-lg font-bold text-ink">الأمان</h2>
            <x-form.checkbox name="force_password_change" label="إلزام تغيير كلمة المرور عند أول دخول"
                :checked="$settings['force_password_change']"
                hint="عند التفعيل: كل حساب حدّدت الإدارة كلمة مروره (عند الإنشاء أو الاستيراد أو إعادة التعيين) يُطلب منه اختيار كلمة مرور جديدة قبل استخدام المنصة." />
        </section>

        <section aria-labelledby="progress-title" class="space-y-4 border-t border-line pt-6">
            <h2 id="progress-title" class="font-heading text-lg font-bold text-ink">التقدّم والدرجات</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="weekly_content_goal" type="number" min="1" max="50" required label="هدف الإنجاز الأسبوعي (عدد الدروس والمقاطع)"
                    :value="$settings['weekly_content_goal']" hint="يُستخدم لحساب نسبة الأسبوع في لوحة الطالبة وصفحة تقدّمي." />
                <x-form.input name="default_target_score" type="number" min="1" max="100" required label="الدرجة المستهدفة الافتراضية"
                    :value="$settings['default_target_score']" hint="تُستخدم عندما لا تحدّد الطالبة هدفًا لمحاولتها." />
            </div>
            <x-form.checkbox name="enable_gamification" label="تفعيل الأوسمة" :checked="$settings['enable_gamification']"
                hint="أوسمة شخصية إيجابية تظهر للطالبة في صفحة تقدّمي فقط. لا يوجد ترتيب عام بين الطالبات." />
        </section>

        <section aria-labelledby="alerts-title" class="space-y-4 border-t border-line pt-6">
            <h2 id="alerts-title" class="font-heading text-lg font-bold text-ink">التنبيهات الذكية</h2>
            <p class="text-sm text-muted">تُطبّق القيم الجديدة في التحديث اليومي التالي للتنبيهات.</p>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-form.input name="inactivity_days" type="number" min="1" max="60" required label="أيام عدم النشاط"
                    :value="$settings['inactivity_days']" hint="تُعدّ الطالبة غير نشطة بعد هذا العدد من الأيام دون نشاط." />
                <x-form.input name="upcoming_exam_alert_days" type="number" min="1" max="90" required label="أيام تنبيه الاختبار القادم"
                    :value="$settings['upcoming_exam_alert_days']" hint="يُعدّ الاختبار قريبًا إذا بقي عليه هذا العدد من الأيام أو أقل." />
                <x-form.input name="low_activity_threshold" type="number" min="0" max="50" required label="حد النشاط المنخفض"
                    :value="$settings['low_activity_threshold']" hint="أقل من هذا العدد من الأنشطة في الأسبوع مع اختبار قريب يُنشئ تنبيهًا." />
            </div>
        </section>

        <section aria-labelledby="email-title" class="space-y-3 border-t border-line pt-6">
            <h2 id="email-title" class="font-heading text-lg font-bold text-ink">البريد</h2>
            <x-form.checkbox name="email_notifications" label="إرسال الإشعارات بالبريد أيضًا" :checked="$settings['email_notifications']"
                hint="يُرسل البريد فقط لمن لديها بريد إلكتروني في حسابها. الإشعارات داخل المنصة تُرسل دائمًا." />
        </section>

        <section aria-labelledby="support-title" class="space-y-4 border-t border-line pt-6">
            <h2 id="support-title" class="font-heading text-lg font-bold text-ink">التواصل</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="support_email" type="email" label="بريد الدعم" :value="$settings['support_email']"
                    hint="يظهر في صفحة عن المنصة وأسفل الصفحات العامة. اتركيه فارغًا لإخفائه." />
                <x-form.input name="support_phone" type="tel" label="هاتف الدعم" :value="$settings['support_phone']"
                    hint="اتركيه فارغًا لإخفائه." />
            </div>
        </section>

        <section aria-labelledby="pages-title" class="space-y-4 border-t border-line pt-6">
            <h2 id="pages-title" class="font-heading text-lg font-bold text-ink">الصفحات العامة</h2>
            <p class="text-sm text-muted">نص عادي؛ يفصل السطر الفارغ بين الفقرات. الصفحة التي لا نص لها ولا رابط تظهر بعبارة «قريبًا» ولا تظهر في الروابط السفلية.</p>
            <x-form.textarea name="about_text" label="نص صفحة «عن المنصة»" rows="5" maxlength="20000" :value="$settings['about_text']"
                hint="إذا تُرك فارغًا يظهر الوصف العام للمنصة." />
            <x-form.textarea name="privacy_text" label="نص سياسة الخصوصية" rows="6" maxlength="20000" :value="$settings['privacy_text']" />
            <x-form.input name="privacy_url" type="url" label="رابط خارجي لسياسة الخصوصية (اختياري)" :value="$settings['privacy_url']"
                hint="إن وُجدت نسخة رسمية في موقع آخر. يجب أن يبدأ بـ https://" />
            <x-form.textarea name="terms_text" label="نص الشروط والأحكام" rows="6" maxlength="20000" :value="$settings['terms_text']" />
            <x-form.input name="terms_url" type="url" label="رابط خارجي للشروط والأحكام (اختياري)" :value="$settings['terms_url']"
                hint="يجب أن يبدأ بـ https://" />
        </section>

        <x-primary-button>حفظ الإعدادات</x-primary-button>
    </form>
</x-app-layout>
