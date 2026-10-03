@props(['next', 'editable' => false])

{{-- $next: ['attempt' => ExamAttempt, 'days' => int] or null --}}
<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-card bg-brand-gradient p-6 text-white shadow-card']) }} aria-labelledby="next-exam-title">
    <p id="next-exam-title" class="text-sm text-white/85">الاختبار القادم</p>
    @if ($next)
        <p class="mt-1 font-heading text-2xl font-bold">
            {{ $next['attempt']->exam_type->label() }} — {{ \App\Support\ArabicDays::until($next['days']) }}
        </p>
        <p class="mt-2 text-sm text-white/90">
            <span dir="ltr">{{ $next['attempt']->exam_date->format('Y-m-d') }}</span>
            · المحاولة {{ $next['attempt']->attempt_number }}
            · {{ $next['attempt']->booking_status->label() }}
        </p>
    @else
        <p class="mt-1 font-heading text-xl font-bold">لا يوجد اختبار محجوز قادم</p>
        @if ($editable)
            <a href="{{ route('student.exams.create') }}" class="btn mt-4 bg-white text-brand-800 hover:bg-brand-50">أضيفي موعد اختبارك</a>
        @endif
    @endif
</section>
