@php
    $back = match ($content->section) {
        \App\Enums\ContentSection::QUANTITATIVE => route('student.quantitative'),
        \App\Enums\ContentSection::VERBAL => route('student.verbal'),
        \App\Enums\ContentSection::TAHSILI => $content->subject ? route('student.achievement.subject', $content->subject) : route('student.achievement'),
    };
@endphp

<x-app-layout area="student" :title="$content->title">
    <a href="{{ $back }}" class="btn-ghost -ms-3 mb-2 px-3">
        <x-icon name="chevron-left" class="h-4 w-4 rotate-180" /> {{ $content->section->label() }}
    </a>

    <article class="card overflow-hidden">
        @if ($content->embedUrl())
            <div class="aspect-video w-full bg-ink">
                <iframe src="{{ $content->embedUrl() }}" title="{{ $content->title }}" class="h-full w-full"
                    allow="accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen"
                    referrerpolicy="strict-origin-when-cross-origin" loading="lazy" allowfullscreen></iframe>
            </div>
        @endif

        <div class="space-y-4 p-6">
            <div class="flex flex-wrap gap-1.5">
                <x-badge>{{ $content->content_type->label() }}</x-badge>
                @if ($content->stage) <x-badge color="gray">{{ $content->stage->label() }}</x-badge> @endif
                @if ($content->difficulty) <x-badge color="gray">{{ $content->difficulty->label() }}</x-badge> @endif
                @if ($content->durationMinutes()) <x-badge color="gray">{{ $content->durationMinutes() }} دقيقة</x-badge> @endif
                <x-source-badge :source="$content->source" />
            </div>

            <div class="flex flex-wrap items-start justify-between gap-3">
                <h1 class="text-2xl font-bold text-ink">{{ $content->title }}</h1>
                <form method="POST" action="{{ route('student.content.favorite', $content) }}">
                    @csrf
                    <button type="submit" class="btn-secondary min-h-[40px] px-3" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}">
                        <x-icon name="bookmark" @class(['h-5 w-5', 'fill-brand-600 text-brand-600' => $isFavorite]) />
                        {{ $isFavorite ? 'في المفضلة' : 'أضيفي للمفضلة' }}
                    </button>
                </form>
            </div>

            <p class="text-sm text-muted">
                {{ $content->category?->name ?? collect([$content->subject?->name, $content->chapter?->name, $content->topic?->name])->filter()->join(' — ') }}
            </p>

            @if ($content->description)
                <p class="leading-relaxed text-ink">{{ $content->description }}</p>
            @endif

            @if ($content->body)
                <div class="prose max-w-none leading-loose text-ink">{!! nl2br(e($content->body)) !!}</div>
            @endif

            @if ($content->external_url)
                <a href="{{ $content->external_url }}" target="_blank" rel="noopener noreferrer" class="btn-primary">
                    <x-icon name="link" /> فتح المصدر
                    <span class="sr-only">(يفتح في نافذة جديدة)</span>
                </a>
            @endif

            @if ($quiz)
                @php($answers = $lastAttempt?->answers->keyBy('quiz_question_id') ?? collect())
                @if ($lastAttempt)
                    <section id="quiz-result" class="space-y-4 rounded-xl border border-line p-4" aria-labelledby="quiz-result-title">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 id="quiz-result-title" class="font-heading text-lg font-semibold text-ink">نتيجة آخر محاولة</h2>
                                <p class="mt-1 text-sm text-muted">
                                    {{ $lastAttempt->correct_count }} من {{ $lastAttempt->question_count }}
                                    · أفضل نتيجة: <bdi>{{ $bestPercentage }}%</bdi>
                                    · نسبة النجاح: <bdi>{{ $quiz->pass_percentage }}%</bdi>
                                </p>
                            </div>
                            <x-badge :color="$lastAttempt->passed ? 'success' : 'warning'" class="text-base">
                                <bdi>{{ $lastAttempt->percentage }}%</bdi> {{ $lastAttempt->passed ? 'ناجحة' : 'لم تبلغي نسبة النجاح' }}
                            </x-badge>
                        </div>

                        @if ($answers->isNotEmpty())
                            <ol class="space-y-3">
                                @foreach ($quiz->questions as $question)
                                    @php($answer = $answers->get($question->id))
                                    @continue(! $answer)
                                    @php($chosen = $question->options->firstWhere('id', $answer->question_option_id))
                                    @php($correct = $question->options->firstWhere('is_correct', true))
                                    <li @class(['rounded-xl p-3 text-sm', 'bg-emerald-50' => $answer->is_correct, 'bg-red-50' => ! $answer->is_correct])>
                                        <p class="flex items-start gap-2 font-medium text-ink">
                                            <x-icon :name="$answer->is_correct ? 'check-circle' : 'x-circle'" @class(['mt-0.5 h-5 w-5', 'text-emerald-600' => $answer->is_correct, 'text-red-600' => ! $answer->is_correct]) />
                                            <span>{{ $loop->iteration }}. {{ $question->prompt }}</span>
                                        </p>
                                        <p class="ms-7 mt-1 text-ink">إجابتك: <bdi>{{ $chosen?->label ?? '—' }}</bdi></p>
                                        @if (! $answer->is_correct && $correct)
                                            <p class="ms-7 mt-1 text-ink">الإجابة الصحيحة: <bdi>{{ $correct->label }}</bdi></p>
                                        @endif
                                        @if ($question->explanation)
                                            <p class="ms-7 mt-1 text-muted [unicode-bidi:plaintext]">{{ $question->explanation }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </section>
                @endif

                <section class="space-y-4" aria-labelledby="quiz-title">
                    <h2 id="quiz-title" class="font-heading text-lg font-semibold text-ink">
                        {{ $lastAttempt ? 'أعيدي المحاولة' : 'أسئلة الاختبار' }}
                        <span class="text-sm font-normal text-muted">({{ $quiz->questions->count() }} أسئلة)</span>
                    </h2>
                    <x-input-error :messages="$errors->get('answers')" />
                    <form method="POST" action="{{ route('student.content.quiz.submit', $content) }}" class="space-y-4">
                        @csrf
                        @foreach ($quiz->questions as $question)
                            <fieldset class="rounded-xl border border-line p-4">
                                <legend class="px-1 font-medium text-ink">{{ $loop->iteration }}. {{ $question->prompt }}</legend>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach ($question->options as $option)
                                        <label class="flex min-h-[44px] cursor-pointer items-center gap-3 rounded-lg border border-line px-3 py-2 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" required
                                                @checked((int) old('answers.'.$question->id) === $option->id)
                                                class="h-5 w-5 border-line text-brand-600 focus:ring-brand-600">
                                            <span class="text-sm text-ink"><bdi>{{ $option->label }}</bdi></span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                        @endforeach
                        <x-primary-button>إرسال الإجابات</x-primary-button>
                    </form>
                </section>
            @elseif ($content->content_type === \App\Enums\ContentType::QUIZ)
                <p class="flex items-center gap-2 text-sm text-muted"><x-badge>قريبًا</x-badge> تُعدّ أسئلة هذا الاختبار حاليًا.</p>
            @endif

            @php($status = $progress?->status ?? \App\Enums\ProgressStatus::NOT_STARTED)
            <section class="flex flex-col gap-3 rounded-xl bg-brand-50 p-4 sm:flex-row sm:items-center sm:justify-between" aria-labelledby="progress-title">
                <div>
                    <h2 id="progress-title" class="sr-only">حالة الإنجاز</h2>
                    <p class="text-sm text-muted">حالتك في هذا المحتوى</p>
                    <x-badge :color="$status->color()" class="mt-1">{{ $status->label() }}</x-badge>
                    @if ($progress?->completed_at)
                        <span class="ms-2 text-xs text-muted" dir="ltr">{{ $progress->completed_at->format('Y-m-d') }}</span>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($status === \App\Enums\ProgressStatus::NOT_STARTED)
                        <form method="POST" action="{{ route('student.content.start', $content) }}">
                            @csrf
                            <button class="btn-secondary"><x-icon name="play-circle" /> ابدأ</button>
                        </form>
                    @endif
                    @if ($quiz && $status !== \App\Enums\ProgressStatus::COMPLETED)
                        <p class="text-sm text-muted">يُحتسب منجزًا عند بلوغ نسبة النجاح.</p>
                    @elseif ($status !== \App\Enums\ProgressStatus::COMPLETED)
                        <form method="POST" action="{{ route('student.content.complete', $content) }}">
                            @csrf
                            <x-primary-button><x-icon name="check-circle" /> أنجزت</x-primary-button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('student.content.uncomplete', $content) }}">
                            @csrf
                            <button class="btn-ghost">التراجع عن الإنجاز</button>
                        </form>
                    @endif
                </div>
            </section>

            @if ($content->source)
                <p class="border-t border-line pt-4 text-xs text-muted">
                    المصدر: {{ $content->source->name }}. تمكّن منصة متابعة وتنظيم ولا تعيد نشر المحتوى المملوك لغيرها.
                </p>
            @endif
        </div>
    </article>
</x-app-layout>
