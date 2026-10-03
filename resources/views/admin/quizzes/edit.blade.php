@php
    $field = 'block w-full rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600';
@endphp

<x-app-layout area="admin" title="أسئلة الاختبار">
    <x-page-header title="أسئلة الاختبار" :description="$content->title">
        <x-slot:actions>
            <a href="{{ route('admin.content.edit', $content) }}" class="btn-secondary">بيانات المحتوى</a>
        </x-slot:actions>
    </x-page-header>

    @if ($attemptsCount > 0)
        <x-alert type="info" class="mt-6">
            حلّت الطالبات هذا الاختبار {{ $attemptsCount }} مرة. حفظ الأسئلة يحذف تفاصيل الإجابات السابقة، وتبقى درجات المحاولات كما هي.
        </x-alert>
    @endif

    <form method="POST" action="{{ route('admin.content.quiz.update', $content) }}" class="mt-6 space-y-6"
        x-data="{ count: {{ $visibleCount }}, max: {{ count($questions) }} }">
        @csrf
        @method('PUT')

        <div class="card grid gap-5 p-6 sm:grid-cols-3 sm:items-end">
            <x-form.input name="pass_percentage" type="number" min="1" max="100" label="نسبة النجاح (%)" :value="$passPercentage" required
                hint="عند بلوغها يُحتسب المحتوى منجزًا للطالبة." />
        </div>
        <x-input-error :messages="$errors->get('questions')" />

        @foreach ($questions as $i => $question)
            <fieldset class="card space-y-4 p-6" x-data="{ type: @js($question['question_type']) }"
                x-show="{{ $i }} < count" :disabled="{{ $i }} >= count" @if ($i >= $visibleCount) disabled hidden @endif>
                <legend class="font-heading font-semibold text-ink">السؤال {{ $i + 1 }}</legend>
                <div class="max-w-xs">
                    <x-input-label :for="'q'.$i.'_type'" value="نوع السؤال" />
                    <select id="q{{ $i }}_type" name="questions[{{ $i }}][question_type]" x-model="type" class="{{ $field }} min-h-[44px]">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected($question['question_type'] === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label :for="'q'.$i.'_prompt'" value="نص السؤال" />
                    <textarea id="q{{ $i }}_prompt" name="questions[{{ $i }}][prompt]" rows="3" class="{{ $field }}">{{ $question['prompt'] }}</textarea>
                    <x-input-error :messages="$errors->get('questions.'.$i.'.prompt')" />
                </div>

                <div x-show="type === 'multiple_choice'" class="space-y-2">
                    <p class="text-sm font-medium text-ink">الخيارات (حدّدي الإجابة الصحيحة)</p>
                    @foreach (range(0, 3) as $j)
                        <div class="flex items-center gap-3">
                            <input type="radio" name="questions[{{ $i }}][correct]" value="{{ $j }}" @checked((int) $question['correct'] === $j) :disabled="type !== 'multiple_choice'"
                                class="h-5 w-5 border-line text-brand-600 focus:ring-brand-600" aria-label="الخيار {{ $j + 1 }} هو الصحيح">
                            <input type="text" name="questions[{{ $i }}][options][{{ $j }}]" value="{{ $question['options'][$j] ?? '' }}" placeholder="الخيار {{ $j + 1 }}"
                                aria-label="الخيار {{ $j + 1 }}" class="{{ $field }} min-h-[44px]">
                        </div>
                    @endforeach
                    <x-input-error :messages="$errors->get('questions.'.$i.'.options')" />
                </div>
                <div x-show="type === 'true_false'" class="flex flex-wrap gap-6">
                    @foreach (['صح', 'خطأ'] as $j => $label)
                        <label class="inline-flex min-h-[44px] items-center gap-2">
                            <input type="radio" name="questions[{{ $i }}][correct]" value="{{ $j }}" @checked((int) $question['correct'] === $j) :disabled="type !== 'true_false'"
                                class="h-5 w-5 border-line text-brand-600 focus:ring-brand-600">
                            <span class="text-sm">{{ $label }} هي الإجابة الصحيحة</span>
                        </label>
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('questions.'.$i.'.correct')" />

                <div>
                    <x-input-label :for="'q'.$i.'_explanation'" value="الشرح بعد الإجابة (اختياري)" />
                    <textarea id="q{{ $i }}_explanation" name="questions[{{ $i }}][explanation]" rows="2" class="{{ $field }}">{{ $question['explanation'] }}</textarea>
                </div>
            </fieldset>
        @endforeach

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="btn-secondary" x-on:click="count++" x-show="count < max">
                <x-icon name="plus" /> إضافة سؤال
            </button>
            <button type="button" class="btn-ghost" x-on:click="count--" x-show="count > 1">حذف آخر سؤال</button>
        </div>

        <div class="flex gap-2 border-t border-line pt-6">
            <x-primary-button>حفظ الأسئلة</x-primary-button>
            <a href="{{ route('admin.content.index') }}" class="btn-secondary">رجوع</a>
        </div>
    </form>
</x-app-layout>
