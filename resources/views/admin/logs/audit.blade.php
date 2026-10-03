<x-app-layout area="admin" title="سجل التدقيق">
    <x-page-header title="سجل التدقيق" description="كل إجراء مهم للإدارة والموجهات. السجل للقراءة فقط." />

    <form method="GET" class="card mt-6 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
        <x-form.select name="action" label="نوع الإجراء" :options="$actions->mapWithKeys(fn ($a) => [$a => $a])" :value="request('action')" placeholder="الكل" />
        <x-form.input name="user" label="المستخدم" :value="request('user')" />
        <x-form.input name="from" type="date" label="من" :value="request('from')" />
        <x-form.input name="to" type="date" label="إلى" :value="request('to')" />
        <button class="btn-secondary">تصفية</button>
    </form>

    <div class="mt-6">
        @if ($logs->isEmpty())
            <x-empty-state icon="clipboard-document-list" title="لا توجد سجلات مطابقة" />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الوقت</th>
                    <th scope="col" class="px-4 py-3 text-start">المستخدم</th>
                    <th scope="col" class="px-4 py-3 text-start">الإجراء</th>
                    <th scope="col" class="px-4 py-3 text-start">السجل</th>
                    <th scope="col" class="px-4 py-3 text-start">التغييرات</th>
                    <th scope="col" class="px-4 py-3 text-start">IP</th>
                </x-slot:head>
                @foreach ($logs as $log)
                    <tr class="align-top">
                        <td class="whitespace-nowrap px-4 py-3" dir="ltr">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'النظام' }}</td>
                        <td class="px-4 py-3" dir="ltr"><code class="text-xs">{{ $log->action }}</code></td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-muted" dir="ltr">{{ $log->auditable_type ? class_basename($log->auditable_type).' #'.$log->auditable_id : '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($log->old_values || $log->new_values)
                                <details>
                                    <summary class="cursor-pointer text-sm text-brand-700">عرض</summary>
                                    <pre class="mt-2 max-w-md overflow-x-auto whitespace-pre-wrap rounded-lg bg-brand-50 p-2 text-xs" dir="ltr">{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                </details>
                            @else
                                —
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-muted" dir="ltr">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                @endforeach
            </x-table>
            {{ $logs->links() }}
        @endif
    </div>
</x-app-layout>
