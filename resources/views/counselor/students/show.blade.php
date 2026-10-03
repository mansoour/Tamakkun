<x-app-layout area="counselor" :title="$student->user->name">
    <a href="{{ route('counselor.students.index') }}" class="btn-ghost -ms-3 mb-2 px-3">
        <x-icon name="chevron-left" class="h-4 w-4 rotate-180" /> العودة إلى الطالبات
    </a>

    <section class="card p-6">
        <h1 class="text-2xl font-bold text-ink">{{ $student->user->name }}</h1>
        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="text-muted">رقم الطالبة</dt><dd class="mt-1 font-medium" dir="ltr">{{ $student->student_code }}</dd></div>
            <div><dt class="text-muted">المدرسة</dt><dd class="mt-1 font-medium">{{ $student->school->name }}</dd></div>
            <div><dt class="text-muted">الفصل</dt><dd class="mt-1 font-medium">{{ $student->classroom?->label() ?? '—' }}</dd></div>
            <div><dt class="text-muted">آخر دخول</dt><dd class="mt-1 font-medium" dir="ltr">{{ $student->user->last_login_at?->format('Y-m-d H:i') ?? '—' }}</dd></div>
        </dl>
    </section>

    <section class="mt-6" aria-labelledby="progress-title">
        <h2 id="progress-title" class="text-lg font-semibold text-ink">التقدّم في المحتوى</h2>
        <x-student-progress-card :summary="$summary" class="mt-3" />
        <p class="mt-2 text-sm text-muted">
            آخر نشاط تعلّم:
            <span dir="ltr">{{ $summary['last_activity_at']?->format('Y-m-d H:i') ?? '—' }}</span>
        </p>
    </section>

    <x-dev-notice class="mt-6">
        ستظهر هنا في المراحل القادمة درجات الطالبة ومواعيد اختباراتها والتنبيهات والملاحظات.
    </x-dev-notice>
</x-app-layout>
