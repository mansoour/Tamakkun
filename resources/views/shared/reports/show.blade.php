<x-app-layout :area="$area" :title="$report['title']">
    <a href="{{ route($area.'.reports.index') }}" class="btn-ghost -ms-3 mb-2 px-3"><x-icon name="chevron-left" class="h-4 w-4 rotate-180" /> التقارير</a>
    <x-page-header :title="$report['title']" :description="$report['description']">
        <x-slot:actions>
            <a href="{{ route($area.'.reports.show', [$report['key'], 'format' => 'pdf']) }}" class="btn-primary"><x-icon name="document-text" /> PDF</a>
            <a href="{{ route($area.'.reports.show', [$report['key'], 'format' => 'csv']) }}" class="btn-secondary">CSV</a>
        </x-slot:actions>
    </x-page-header>

    <p class="mt-2 text-xs text-muted">أُنشئ في <span dir="ltr">{{ $report['generated_at']->format('Y-m-d H:i') }}</span> · {{ count($report['rows']) }} صف</p>

    <div class="mt-4">
        @if (empty($report['rows']))
            <x-empty-state icon="document-text" title="لا توجد بيانات لهذا التقرير" />
        @else
            <x-table>
                <x-slot:head>
                    @foreach ($report['columns'] as $column)
                        <th scope="col" class="whitespace-nowrap px-4 py-3 text-start">{{ $column }}</th>
                    @endforeach
                </x-slot:head>
                @foreach ($report['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td class="px-4 py-3"><bdi>{{ $cell }}</bdi></td>
                        @endforeach
                    </tr>
                @endforeach
            </x-table>
        @endif
    </div>
</x-app-layout>
