<section class="card h-fit space-y-4 p-6" aria-labelledby="account-status-title">
    <h2 id="account-status-title" class="font-heading font-bold text-ink">حالة الحساب</h2>
    <p class="text-sm text-muted">
        الحالة الحالية: <x-badge :color="$user->status->color()">{{ $user->status->label() }}</x-badge>
    </p>
    <p class="text-xs text-muted">لا يستطيع الدخول إلا الحساب النشط. يُسجَّل كل تغيير في سجل التدقيق.</p>

    <form method="POST" action="{{ route('admin.users.status', $user) }}" class="space-y-3">
        @csrf
        @method('PATCH')
        <x-form.select name="status" label="تغيير الحالة إلى" :value="$user->status->value"
            :options="collect(\App\Enums\UserStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
        <button type="submit" class="btn-secondary w-full">تحديث الحالة</button>
    </form>
</section>
