<x-app-layout area="admin" :title="$title">
    <x-page-header :title="$title" :description="$description">
        <x-slot:actions>
            <a href="{{ route($routeBase.'.create', request()->only(collect($filters)->pluck('name')->all())) }}" class="btn-primary">إضافة {{ $singular }}</a>
        </x-slot:actions>
    </x-page-header>

    @if ($filters)
        <form method="GET" class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-end">
            @foreach ($filters as $filter)
                <div class="sm:w-80">
                    <x-form.select :name="$filter['name']" :label="$filter['label']" :options="$filter['options']" :value="request($filter['name'])" placeholder="الكل" />
                </div>
            @endforeach
            <button class="btn-secondary">تصفية</button>
        </form>
    @endif

    <div class="mt-6">
        @if ($records->isEmpty())
            <x-empty-state title="لا توجد سجلات" />
        @else
            <x-table>
                <x-slot:head>
                    @foreach (array_keys($columns) as $label)
                        <th scope="col" class="px-4 py-3 text-start">{{ $label }}</th>
                    @endforeach
                    <th scope="col" class="px-4 py-3"><span class="sr-only">إجراءات</span></th>
                </x-slot:head>
                @foreach ($records as $record)
                    <tr>
                        @foreach ($columns as $value)
                            <td class="px-4 py-3 {{ $loop->first ? 'font-medium' : '' }}">{{ $value($record) }}</td>
                        @endforeach
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route($routeBase.'.edit', $record->id) }}" class="btn-ghost min-h-[40px] px-3">تعديل</a>
                                <x-delete-button :action="route($routeBase.'.destroy', $record->id)" />
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-table>
            {{ $records->links() }}
        @endif
    </div>
</x-app-layout>
