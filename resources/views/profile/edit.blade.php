<x-app-layout title="حسابي">
    <h1 class="text-2xl font-extrabold text-ink">حسابي</h1>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card p-6" aria-labelledby="account-info-title">
            <h2 id="account-info-title" class="text-lg font-bold text-ink">بيانات الحساب</h2>
            <p class="mt-1 text-sm text-muted">تُدار هذه البيانات من قِبل المدرسة. لتعديلها تواصلي مع الإدارة.</p>

            <dl class="mt-6 divide-y divide-line text-sm">
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-muted">الاسم</dt>
                    <dd class="font-medium text-ink">{{ $user->name }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-muted">اسم المستخدم</dt>
                    <dd class="font-medium text-ink" dir="ltr">{{ $user->username }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-muted">البريد الإلكتروني</dt>
                    <dd class="font-medium text-ink" dir="ltr">{{ $user->email ?? '—' }}</dd>
                </div>
                <div class="flex justify-between gap-4 py-3">
                    <dt class="text-muted">حالة الحساب</dt>
                    <dd><x-badge color="success">{{ $user->status->label() }}</x-badge></dd>
                </div>
            </dl>
        </section>

        <section class="card p-6">
            @include('profile.partials.update-password-form')
        </section>
    </div>
</x-app-layout>
