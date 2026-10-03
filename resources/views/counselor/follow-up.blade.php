<x-app-layout area="counselor" title="تحتاج متابعة">
    <x-page-header title="تحتاج متابعة" description="طالبات لديهن تنبيهات مفتوحة، أو حالة متابعة يدوية (تحت الملاحظة، تحتاج متابعة، تم التواصل)." />

    <div class="mt-6">
        @if ($rows->isEmpty())
            <x-empty-state icon="check-circle" title="لا توجد طالبات بحاجة لمتابعة الآن" />
        @else
            @include('counselor.partials.roster-table', ['rows' => $rows])
        @endif
    </div>
</x-app-layout>
