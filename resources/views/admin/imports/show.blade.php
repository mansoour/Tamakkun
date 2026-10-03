@php($status = $import->status)

<x-app-layout area="admin" title="نتيجة الاستيراد">
    <x-page-header title="استيراد الطالبات" :description="$import->original_filename">
        <x-slot:actions>
            <a href="{{ route('admin.imports.create') }}" class="btn-secondary">رفع ملف آخر</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        <x-stat-card label="صفوف الملف" :value="$import->total_rows" icon="document-text" />
        <x-stat-card label="صفوف بها أخطاء" :value="count($import->row_errors ?? [])" icon="exclamation-triangle" />
        <x-stat-card label="الحالة" :value="$status->label()" icon="check-circle" />
    </div>

    <div class="mt-6 space-y-6">
        @if ($status === \App\Enums\ImportStatus::PREVIEWED && $import->hasErrors())
            <x-alert type="danger" title="لا يمكن الاستيراد">صحّحي الأخطاء التالية في الملف ثم أعيدي رفعه. لم يُحفظ أي شيء.</x-alert>

            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الصف في الملف</th>
                    <th scope="col" class="px-4 py-3 text-start">الأخطاء</th>
                </x-slot:head>
                @foreach ($import->row_errors as $error)
                    <tr>
                        <td class="px-4 py-3 align-top">{{ $error['row'] ?? 'الملف' }}</td>
                        <td class="px-4 py-3">
                            <ul class="list-disc space-y-1 ps-5 text-red-800">
                                @foreach ($error['messages'] as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </td>
                    </tr>
                @endforeach
            </x-table>
        @elseif ($status === \App\Enums\ImportStatus::PREVIEWED)
            <x-alert type="success" title="الملف صالح">
                جميع الصفوف ({{ $import->total_rows }}) صحيحة. راجعي المعاينة ثم أكّدي الاستيراد.
            </x-alert>

            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الاسم</th>
                    <th scope="col" class="px-4 py-3 text-start">رقم الطالبة</th>
                    <th scope="col" class="px-4 py-3 text-start">اسم المستخدم</th>
                </x-slot:head>
                @foreach ($preview as $row)
                    <tr>
                        <td class="px-4 py-3">{{ $row['name'] }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $row['student_code'] }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $row['username'] }}</td>
                    </tr>
                @endforeach
            </x-table>
            @if ($import->total_rows > $preview->count())
                <p class="text-sm text-muted">تُعرض أول {{ $preview->count() }} صفوف فقط.</p>
            @endif

            <form method="POST" action="{{ route('admin.imports.confirm', $import) }}">
                @csrf
                <x-primary-button>تأكيد استيراد {{ $import->total_rows }} طالبة</x-primary-button>
            </form>
        @elseif ($status === \App\Enums\ImportStatus::QUEUED)
            <x-alert type="info" title="جارٍ الاستيراد">يجري إنشاء الحسابات في الخلفية. حدّثي الصفحة بعد قليل لرؤية النتيجة.</x-alert>
            <a href="{{ route('admin.imports.show', $import) }}" class="btn-secondary">تحديث</a>
        @elseif ($status === \App\Enums\ImportStatus::COMPLETED)
            <x-alert type="success" title="اكتمل الاستيراد">
                أُنشئ {{ $import->imported_rows }} حساب طالبة بنجاح.
            </x-alert>
            <a href="{{ route('admin.students.index') }}" class="btn-primary">عرض الطالبات</a>
        @else
            <x-alert type="danger" title="فشل الاستيراد">{{ $import->error_message }} لم يُحفظ أي حساب.</x-alert>
        @endif
    </div>
</x-app-layout>
