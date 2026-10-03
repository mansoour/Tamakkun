@props(['alert', 'showStudent' => false, 'actions' => true])

<div {{ $attributes->merge(['class' => 'card flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
            <x-badge :color="$alert->severity->color()">{{ $alert->severity->label() }}</x-badge>
            <span class="font-heading text-sm font-semibold text-ink">{{ $alert->title }}</span>
            @if ($alert->status !== \App\Enums\AlertStatus::OPEN)
                <x-badge color="gray">{{ $alert->status->label() }}</x-badge>
            @endif
        </div>
        @if ($showStudent && $alert->student?->studentProfile)
            <a href="{{ route('counselor.students.show', $alert->student->studentProfile) }}" class="mt-1 block text-sm font-medium text-brand-700 underline-offset-4 hover:underline">{{ $alert->student->name }}</a>
        @endif
        <p class="mt-1 text-sm text-muted">{{ $alert->message }}</p>
        <p class="mt-1 text-xs text-muted" dir="ltr">{{ $alert->generated_at->format('Y-m-d') }}</p>
    </div>
    @if ($actions && $alert->status !== \App\Enums\AlertStatus::RESOLVED)
        <div class="flex shrink-0 gap-1">
            @if ($alert->status === \App\Enums\AlertStatus::OPEN)
                <form method="POST" action="{{ route('counselor.alerts.acknowledge', $alert) }}">@csrf<button class="btn-ghost min-h-[40px] px-3">اطّلعت</button></form>
            @endif
            <form method="POST" action="{{ route('counselor.alerts.resolve', $alert) }}">@csrf<button class="btn-secondary min-h-[40px] px-3">تمت المعالجة</button></form>
        </div>
    @endif
</div>
