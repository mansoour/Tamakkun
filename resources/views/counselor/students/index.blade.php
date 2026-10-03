<x-app-layout area="counselor" title="الطالبات">
    <x-page-header title="طالباتي" description="كل الأعمدة محسوبة من بيانات الطالبة الفعلية." />

    <form method="GET" class="card mt-6 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        <x-form.input name="q" label="بحث" :value="request('q')" placeholder="الاسم أو رقم الطالبة" />
        <x-form.select name="classroom_id" label="الفصل" :options="$classrooms" :value="request('classroom_id')" placeholder="كل الفصول" />
        <x-form.select name="booking" label="الحجز" :options="['booked' => 'محجوز', 'not_booked' => 'لم تحجز']" :value="request('booking')" placeholder="الكل" />
        <x-form.select name="exam_type" label="الاختبار القادم" :options="collect($examTypes)->mapWithKeys(fn ($t) => [$t->value => $t->label()])" :value="request('exam_type')" placeholder="الكل" />
        <x-form.select name="activity" label="النشاط" :options="['active' => 'نشطة', 'inactive' => 'غير نشطة']" :value="request('activity')" placeholder="الكل" />
        <x-form.select name="completion" label="الإنجاز" :options="['low' => 'أقل من 40%', 'mid' => '40% – 74%', 'high' => '75% فأكثر']" :value="request('completion')" placeholder="الكل" />
        <x-form.select name="follow_up" label="المتابعة" :value="request('follow_up')" placeholder="الكل"
            :options="['attention' => 'تحتاج متابعة (تلقائي أو يدوي)'] + collect($followUpStatuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])->all()" />
        <div class="flex gap-2">
            <div class="flex-1"><x-form.input name="score_below" type="number" min="0" max="100" label="أفضل قدرات أقل من" :value="request('score_below')" /></div>
            <button class="btn-primary self-end">تصفية</button>
        </div>
    </form>

    <div class="mt-6">
        @if ($students->isEmpty())
            <x-empty-state icon="users" title="لا توجد طالبات مطابقة" description="لم تُسند إليكِ طالبات بعد، أو لا توجد نتائج لهذه التصفية." />
        @else
            @include('counselor.partials.roster-table', ['rows' => $students])
            {{ $students->links() }}
        @endif
    </div>
</x-app-layout>
