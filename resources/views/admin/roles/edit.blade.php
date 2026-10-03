<x-app-layout area="admin" :title="'صلاحيات '.$roleLabel">
    <x-page-header :title="'صلاحيات '.$roleLabel" description="اختاري الصلاحيات التي يمنحها هذا الدور. يسري التغيير فورًا على كل من يحمل الدور." />

    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="card mt-6 max-w-3xl space-y-5 p-6">
        @csrf
        @method('PUT')

        <x-input-error :messages="$errors->get('permissions')" />

        @php($granted = old('permissions', $role->permissions->pluck('name')->all()))

        <fieldset>
            <legend class="sr-only">الصلاحيات</legend>
            <ul class="divide-y divide-line">
                @foreach ($permissions as $permission)
                    @php($locked = in_array($permission, $protected, true))
                    <li>
                        @if ($locked)
                            {{-- Disabled boxes are not submitted, so the locked value is sent here. --}}
                            <input type="hidden" name="permissions[]" value="{{ $permission->value }}">
                        @endif
                        <label for="perm-{{ $loop->index }}" class="flex min-h-[44px] items-start gap-3 py-3">
                            <input id="perm-{{ $loop->index }}" type="checkbox" name="permissions[]" value="{{ $permission->value }}"
                                @checked(in_array($permission->value, $granted, true))
                                @if ($locked) checked disabled aria-describedby="locked-hint" @endif
                                class="mt-0.5 h-5 w-5 rounded border-line text-brand-600 focus:ring-brand-600">
                            <span>
                                <span class="block text-sm font-medium text-ink">{{ $permission->label() }}</span>
                                <span class="block text-xs text-muted" dir="ltr">{{ $permission->value }}</span>
                                @if ($locked)
                                    <span class="mt-1 block text-xs text-amber-700">لا يمكن إزالتها لأنك تحملين هذا الدور.</span>
                                @endif
                            </span>
                        </label>
                    </li>
                @endforeach
            </ul>
        </fieldset>

        @if ($protected !== [])
            <p id="locked-hint" class="text-xs text-muted">لحمايتك من فقدان الوصول، لا يمكنك إزالة صلاحيتي الدخول إلى لوحة الإدارة وإدارة الأدوار من دور تحملينه.</p>
        @endif

        <div class="flex items-center gap-3">
            <x-primary-button>حفظ الصلاحيات</x-primary-button>
            <a href="{{ route('admin.roles.index') }}" class="btn-secondary">إلغاء</a>
        </div>
    </form>
</x-app-layout>
