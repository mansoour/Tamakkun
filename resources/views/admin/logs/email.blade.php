<x-app-layout area="admin" title="سجل البريد">
    <x-page-header title="سجل البريد" description="كل رسالة بريد صادرة، ناجحة أو فاشلة." />

    <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
        <div class="sm:w-48"><x-form.select name="status" label="الحالة" :options="collect($statuses)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" :value="request('status')" placeholder="الكل" /></div>
        <div class="sm:w-72"><x-form.input name="recipient" label="المستلم" :value="request('recipient')" /></div>
        <button class="btn-secondary">تصفية</button>
    </form>

    <div class="mt-6">
        @if ($logs->isEmpty())
            <x-empty-state icon="envelope" title="لا توجد رسائل" />
        @else
            <x-table>
                <x-slot:head>
                    <th scope="col" class="px-4 py-3 text-start">الوقت</th>
                    <th scope="col" class="px-4 py-3 text-start">المستلم</th>
                    <th scope="col" class="px-4 py-3 text-start">الموضوع</th>
                    <th scope="col" class="px-4 py-3 text-start">القالب</th>
                    <th scope="col" class="px-4 py-3 text-start">الحالة</th>
                </x-slot:head>
                @foreach ($logs as $log)
                    <tr class="align-top">
                        <td class="whitespace-nowrap px-4 py-3" dir="ltr">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-4 py-3" dir="ltr">{{ $log->recipient }}</td>
                        <td class="px-4 py-3">{{ $log->subject ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-muted" dir="ltr">{{ $log->template ? class_basename($log->template) : '—' }}</td>
                        <td class="px-4 py-3">
                            <x-badge :color="$log->status === \App\Enums\EmailStatus::SENT ? 'success' : 'danger'">{{ $log->status->label() }}</x-badge>
                            @if ($log->error_message) <p class="mt-1 text-xs text-red-700">{{ \Illuminate\Support\Str::limit($log->error_message, 160) }}</p> @endif
                        </td>
                    </tr>
                @endforeach
            </x-table>
            {{ $logs->links() }}
        @endif
    </div>
</x-app-layout>
