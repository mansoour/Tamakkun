<x-app-layout area="counselor" title="التنبيهات">
    <x-page-header title="التنبيهات" description="تُولَّد تلقائيًا يوميًا وعند تحديث الاختبارات، وتُغلق تلقائيًا عند زوال سببها." />

    <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="sm:w-48"><x-form.select name="status" label="الحالة" :options="['unresolved' => 'مفتوحة', 'resolved' => 'مغلقة', 'all' => 'الكل']" :value="request('status', 'unresolved')" /></div>
        <div class="sm:w-48"><x-form.select name="severity" label="الأهمية" :options="collect($severities)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="request('severity')" placeholder="الكل" /></div>
        <div class="sm:w-64"><x-form.select name="type" label="النوع" :options="collect($types)->mapWithKeys(fn ($t) => [$t->value => $t->label()])" :value="request('type')" placeholder="الكل" /></div>
        <button class="btn-secondary">تصفية</button>
    </form>

    <div class="mt-6 space-y-3">
        @forelse ($alerts as $alert)
            <x-follow-up-alert :alert="$alert" show-student />
        @empty
            <x-empty-state icon="bell" title="لا توجد تنبيهات مطابقة" />
        @endforelse
        {{ $alerts->links() }}
    </div>
</x-app-layout>
