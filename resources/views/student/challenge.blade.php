<x-app-layout area="student" title="تحدي اليوم">
    <x-page-header title="تحدي اليوم" description="أسئلة سريعة كل يوم تحافظ على سلسلة أيامك. لكل سؤال محاولة واحدة." />

    @error('answer') <x-alert type="danger" class="mt-6">{{ $message }}</x-alert> @enderror

    @if (! $challenge)
        <x-empty-state class="mt-6" icon="bolt" title="لا يوجد تحدٍّ لهذا اليوم" description="عودي غدًا، أو راجعي دروسك في القدرات." />
    @else
        <div class="mt-6 space-y-6">
            @if ($challenge->title)
                <h2 class="text-lg font-semibold text-ink">{{ $challenge->title }}</h2>
            @endif

            @foreach ($challenge->questions as $question)
                @php($answer = $answers->get($question->id))
                <section class="card p-6" aria-labelledby="q-{{ $question->id }}">
                    <div class="flex flex-wrap items-center gap-2">
                        <x-badge>{{ $question->section->label() }}</x-badge>
                        <x-badge color="gray">{{ $question->question_type->label() }}</x-badge>
                        @if ($answer)
                            <x-badge :color="$answer->is_correct ? 'success' : 'danger'">{{ $answer->is_correct ? 'إجابة صحيحة' : 'إجابة غير صحيحة' }}</x-badge>
                        @endif
                    </div>
                    <h3 id="q-{{ $question->id }}" class="mt-3 whitespace-pre-line text-lg font-semibold leading-relaxed text-ink">{{ $question->prompt }}</h3>

                    @if ($answer)
                        <ul class="mt-4 space-y-2">
                            @foreach ($question->options as $option)
                                <li @class([
                                    'flex items-center gap-2 rounded-xl border p-3 text-sm',
                                    'border-emerald-300 bg-emerald-50 text-emerald-900' => $option->is_correct,
                                    'border-red-300 bg-red-50 text-red-900' => ! $option->is_correct && $option->id === $answer->challenge_option_id,
                                    'border-line' => ! $option->is_correct && $option->id !== $answer->challenge_option_id,
                                ])>
                                    @if ($option->is_correct) <x-icon name="check-circle" /> @elseif ($option->id === $answer->challenge_option_id) <x-icon name="x-circle" /> @endif
                                    <span>{{ $option->label }}</span>
                                    @if ($option->id === $answer->challenge_option_id) <span class="ms-auto text-xs">إجابتك</span> @endif
                                </li>
                            @endforeach
                        </ul>
                        @if ($question->explanation)
                            <div class="mt-4 rounded-xl bg-brand-50 p-4 text-sm leading-relaxed text-ink">
                                <p class="font-semibold text-brand-800">الشرح</p>
                                <p class="mt-1 whitespace-pre-line">{{ $question->explanation }}</p>
                            </div>
                        @endif
                    @else
                        <form method="POST" action="{{ route('student.challenge.answer', $question) }}" class="mt-4 space-y-2">
                            @csrf
                            <fieldset>
                                <legend class="sr-only">اختاري إجابة</legend>
                                @foreach ($question->options as $option)
                                    <label class="flex min-h-[48px] cursor-pointer items-center gap-3 rounded-xl border border-line p-3 text-sm hover:bg-brand-50 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                                        <input type="radio" name="option_id" value="{{ $option->id }}" required class="h-5 w-5 border-line text-brand-600 focus:ring-brand-600">
                                        <span>{{ $option->label }}</span>
                                    </label>
                                @endforeach
                            </fieldset>
                            <x-primary-button class="mt-2">تأكيد الإجابة</x-primary-button>
                        </form>
                    @endif
                </section>
            @endforeach
        </div>
    @endif

    @if ($history->isNotEmpty())
        <section class="mt-8" aria-labelledby="history-title">
            <h2 id="history-title" class="text-lg font-semibold text-ink">إجاباتك السابقة</h2>
            <ul class="card mt-3 divide-y divide-line">
                @foreach ($history as $past)
                    <li class="flex items-center justify-between gap-3 p-4 text-sm">
                        <span class="line-clamp-1">{{ $past->question->prompt }}</span>
                        <span class="flex shrink-0 items-center gap-2">
                            <x-badge :color="$past->is_correct ? 'success' : 'danger'">{{ $past->is_correct ? 'صحيحة' : 'غير صحيحة' }}</x-badge>
                            <span class="text-xs text-muted" dir="ltr">{{ $past->answered_at->format('Y-m-d') }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
</x-app-layout>
