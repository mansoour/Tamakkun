<x-app-layout area="admin" :title="$title">
    <x-page-header :title="$title" />

    <form method="POST" action="{{ $record->exists ? route($routeBase.'.update', $record->id) : route($routeBase.'.store') }}" class="card mt-6 max-w-2xl space-y-5 p-6">
        @csrf
        @if ($record->exists) @method('PUT') @endif

        @foreach ($fields as $field)
            @php($value = $record->getAttribute($field['name']))
            @php($value = $value instanceof \BackedEnum ? $value->value : $value)
            @switch($field['type'])
                @case('select')
                    <x-form.select :name="$field['name']" :label="$field['label']" :options="$field['options']" :value="$value"
                        placeholder="اختاري" :required="$field['required'] ?? false" :hint="$field['hint'] ?? null" />
                    @break
                @case('checkbox')
                    <x-form.checkbox :name="$field['name']" :label="$field['label']" :checked="(bool) $value" :hint="$field['hint'] ?? null" />
                    @break
                @case('textarea')
                    <div>
                        <x-input-label :for="$field['name']" :value="$field['label']" />
                        <textarea id="{{ $field['name'] }}" name="{{ $field['name'] }}" rows="4"
                            class="block w-full rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600">{{ old($field['name'], $value) }}</textarea>
                        <x-input-error :messages="$errors->get($field['name'])" />
                    </div>
                    @break
                @default
                    <x-form.input :name="$field['name']" :label="$field['label']" :type="$field['type']" :value="$value"
                        :required="$field['required'] ?? false" :hint="$field['hint'] ?? null" />
            @endswitch
        @endforeach

        <div class="flex gap-2">
            <x-primary-button>حفظ</x-primary-button>
            <a href="{{ route($routeBase.'.index') }}" class="btn-secondary">إلغاء</a>
        </div>
    </form>
</x-app-layout>
