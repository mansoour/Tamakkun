<x-app-layout area="admin" title="الأدوار والصلاحيات">
    <x-page-header title="الأدوار والصلاحيات" description="كل مستخدم يحمل دورًا، وكل دور يمنح مجموعة صلاحيات. يُسجّل كل تغيير في سجل التدقيق." />

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        @foreach ($roles as $role)
            <section class="card flex flex-col p-5" aria-labelledby="role-{{ $role->id }}">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 id="role-{{ $role->id }}" class="font-heading text-lg font-bold text-ink">{{ \App\Enums\RoleName::tryFrom($role->name)?->label() ?? $role->name }}</h2>
                        <p class="mt-1 text-sm text-muted">{{ $role->users_count }} مستخدم · {{ $role->permissions->count() }} صلاحية</p>
                    </div>
                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn-secondary">تعديل</a>
                </div>
                <ul class="mt-4 space-y-1.5 text-sm text-ink">
                    @forelse ($role->permissions->sortBy('id') as $permission)
                        <li class="flex items-start gap-2">
                            <x-icon name="check-circle" class="mt-0.5 h-4 w-4 text-emerald-600" />
                            {{ \App\Enums\PermissionName::tryFrom($permission->name)?->label() ?? $permission->name }}
                        </li>
                    @empty
                        <li class="text-muted">لا صلاحيات.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>
</x-app-layout>
