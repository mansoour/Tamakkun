@php($editing = $attempt->exists)

<x-app-layout area="student" :title="$editing ? 'تعديل اختبار' : 'إضافة اختبار'">
    <x-page-header :title="$editing ? 'تعديل '.$attempt->exam_type->label().' — المحاولة '.$attempt->attempt_number : 'إضافة اختبار'" />

    <form method="POST" action="{{ $editing ? route('student.exams.update', $attempt) : route('student.exams.store') }}"
        class="card mt-6 max-w-2xl space-y-5 p-6"
        x-data="{ status: @js((string) old('booking_status', $attempt->booking_status->value)) }">
        @csrf
        @if ($editing) @method('PUT') @endif

        @unless ($editing)
            <x-form.select name="exam_type" label="نوع الاختبار" :value="$attempt->exam_type->value"
                :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])" required />
        @endunless

        <x-form.select name="booking_status" label="حالة الحجز" :value="$attempt->booking_status->value" x-model="status"
            :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" required />

        <div x-show="status !== 'not_booked'">
            <x-form.input name="exam_date" type="date" label="تاريخ الاختبار" :value="$attempt->exam_date?->format('Y-m-d')" />
        </div>

        <div x-show="status === 'result_received'">
            <x-form.input name="score" type="number" min="0" max="100" label="الدرجة" :value="$attempt->score"
                hint="تُحفظ الدرجة فقط عند اختيار «ظهرت النتيجة»." />
        </div>

        <x-form.input name="target_score" type="number" min="1" max="100" label="الدرجة المستهدفة (اختياري)" :value="$attempt->target_score"
            hint="إن تركتِه فارغًا يُستخدم آخر هدف حدّدتِه، أو الهدف الافتراضي للمنصة." />

        <div>
            <x-input-label for="notes" value="ملاحظات (اختياري)" />
            <textarea id="notes" name="notes" rows="3" class="block w-full rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600">{{ old('notes', $attempt->notes) }}</textarea>
            <x-input-error :messages="$errors->get('notes')" />
        </div>

        <div class="flex gap-2">
            <x-primary-button>حفظ</x-primary-button>
            <a href="{{ route('student.exams.index') }}" class="btn-secondary">إلغاء</a>
        </div>
    </form>
</x-app-layout>
