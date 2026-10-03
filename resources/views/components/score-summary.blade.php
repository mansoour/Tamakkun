@props(['exam'])

{{-- $exam: one entry of ExamProgressService::summary() --}}
<section {{ $attributes->merge(['class' => 'card p-5']) }} aria-labelledby="score-{{ $exam['type']->value }}">
    <div class="flex items-center justify-between gap-2">
        <h2 id="score-{{ $exam['type']->value }}" class="font-heading font-semibold text-ink">{{ $exam['type']->label() }}</h2>
        @if (! $exam['booked'])
            <x-badge color="warning">لم تحجز</x-badge>
        @endif
    </div>
    <dl class="mt-4 grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
        <div>
            <dt class="text-muted">آخر نتيجة</dt>
            <dd class="mt-1 font-heading text-2xl font-bold text-ink">{{ $exam['latest'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-muted">أفضل نتيجة</dt>
            <dd class="mt-1 font-heading text-2xl font-bold text-ink">{{ $exam['best'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-muted">الهدف</dt>
            <dd class="mt-1 font-heading text-2xl font-bold text-brand-700">{{ $exam['target'] }}</dd>
            @if ($exam['target_is_default'])
                <dd class="text-xs text-muted">الهدف الافتراضي</dd>
            @endif
        </div>
        <div>
            <dt class="text-muted">المتبقي للهدف</dt>
            <dd class="mt-1 font-heading text-2xl font-bold text-ink">{{ $exam['gap'] ?? '—' }}</dd>
        </div>
    </dl>
    @if ($exam['improvement'] !== null)
        <p @class(['mt-3 text-sm font-medium', 'text-emerald-700' => $exam['improvement'] > 0, 'text-red-700' => $exam['improvement'] < 0, 'text-muted' => $exam['improvement'] === 0])>
            @if ($exam['improvement'] > 0)
                تحسّن بمقدار {{ \App\Support\ArabicDays::points($exam['improvement']) }} عن المحاولة السابقة
            @elseif ($exam['improvement'] < 0)
                انخفضت بمقدار {{ \App\Support\ArabicDays::points($exam['improvement']) }} عن المحاولة السابقة
            @else
                نفس درجة المحاولة السابقة
            @endif
        </p>
    @endif
</section>
