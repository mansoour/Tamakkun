<x-app-layout area="admin" title="استيراد الطالبات">
    <x-page-header title="استيراد الطالبات من ملف CSV" description="ارفعي الملف، راجعي نتيجة التحقق، ثم أكّدي الاستيراد." />

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data" class="card space-y-5 p-6 lg:col-span-2">
            @csrf
            <div>
                <x-input-label for="file" value="ملف الطالبات (CSV بترميز UTF-8، حتى 2 ميجابايت)" />
                <input id="file" name="file" type="file" accept=".csv,text/csv" required
                    class="block w-full rounded-xl border border-line bg-white text-sm text-ink file:me-4 file:min-h-[44px] file:border-0 file:bg-brand-100 file:px-4 file:font-bold file:text-brand-800">
                <x-input-error :messages="$errors->get('file')" />
            </div>
            <div class="flex flex-wrap gap-2">
                <x-primary-button><x-icon name="document-text" /> رفع ومعاينة</x-primary-button>
                <a href="{{ route('admin.imports.template') }}" class="btn-secondary">تنزيل نموذج الملف</a>
            </div>
        </form>

        <section class="card h-fit space-y-3 p-6 text-sm" aria-labelledby="columns-title">
            <h2 id="columns-title" class="font-heading font-bold text-ink">أعمدة الملف</h2>
            <ul class="space-y-2">
                @foreach ($columns as $column => $aliases)
                    <li class="flex flex-wrap items-center gap-2">
                        <code dir="ltr" class="rounded bg-brand-50 px-1.5 py-0.5 text-xs">{{ $column }}</code>
                        <span class="text-muted">أو «{{ $aliases[1] ?? $column }}»</span>
                        @if (in_array($column, $required)) <x-badge color="warning">مطلوب</x-badge> @endif
                    </li>
                @endforeach
            </ul>
            <p class="text-xs leading-relaxed text-muted">
                يجب أن تكون المدرسة والصف والفصل والموجهة مضافة مسبقًا في النظام. يُبحث عن الصف والفصل في العام الدراسي الحالي للمدرسة،
                وعن الموجهة باسم المستخدم أو البريد. لن يُستورد أي صف ما لم يكن الملف كله صحيحًا.
            </p>
        </section>
    </div>

    @if ($recent->isNotEmpty())
        <section class="mt-8" aria-labelledby="recent-title">
            <h2 id="recent-title" class="text-lg font-bold text-ink">آخر عمليات الاستيراد</h2>
            <x-table class="mt-4">
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الملف</th>
                    <th scope="col" class="px-4 py-3 text-start">بواسطة</th>
                    <th scope="col" class="px-4 py-3 text-start">الصفوف</th>
                    <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                    <th scope="col" class="px-4 py-3 text-start">التاريخ</th>
                </x-slot:head>
                @foreach ($recent as $import)
                    <tr>
                        <td class="px-4 py-3"><a class="text-brand-700 underline-offset-4 hover:underline" href="{{ route('admin.imports.show', $import) }}">{{ $import->original_filename }}</a></td>
                        <td class="px-4 py-3">{{ $import->uploader?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $import->total_rows }}</td>
                        <td class="px-4 py-3"><x-badge :color="$import->status->color()">{{ $import->status->label() }}</x-badge></td>
                        <td class="px-4 py-3 text-muted" dir="ltr">{{ $import->created_at->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
            </x-table>
        </section>
    @endif
</x-app-layout>
