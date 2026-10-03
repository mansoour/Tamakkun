@php
    $editing = $challenge->exists;
    $field = 'block w-full rounded-xl border-line bg-white text-ink shadow-sm focus:border-brand-600 focus:ring-brand-600';
@endphp

<x-app-layout area="admin" :title="$editing ? 'تعديل تحدٍّ' : 'إضافة تحدٍّ'">
    <x-page-header :title="$editing ? 'تعديل تحدٍّ' : 'إضافة تحدٍّ'" />

    <form method="POST" action="{{ $editing ? route('admin.challenges.update', $challenge) : route('admin.challenges.store') }}" class="mt-6 space-y-6">
        @csrf
        @if ($editing) @method('PUT') @endif

        <div class="card grid gap-5 p-6 sm:grid-cols-3 sm:items-end">
            <x-form.input name="challenge_date" type="date" label="تاريخ التحدي" :value="$challenge->challenge_date?->format('Y-m-d')" required />
            <x-form.input name="title" label="عنوان (اختياري)" :value="$challenge->title" />
            <x-form.checkbox name="is_published" label="منشور" :checked="$challenge->is_published" />
        </div>
        <x-input-error :messages="$errors->get('questions')" />

        @foreach ($questions as $i => $question)
            <fieldset class="card space-y-4 p-6" x-data="{ type: @js($question['question_type']) }">
                <legend class="font-heading font-bold text-ink">السؤال {{ $i + 1 }}</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select :name="'questions['.$i.'][section]'" :id="'q'.$i.'_section'" label="القسم" :value="$question['section']"
                        :options="collect($sections)->mapWithKeys(fn ($s) => [$s->value => $s->label()])" />
                    <div>
                        <x-input-label :for="'q'.$i.'_type'" value="نوع السؤال" />
                        <select id="q{{ $i }}_type" name="questions[{{ $i }}][question_type]" x-model="type" class="{{ $field }} min-h-[44px]">
                            @foreach ($types as $type)
                                <option value="{{ $type->value }}" @selected($question['question_type'] === $type->value)>{{ $type->label() }}</option>
                            @endforeach
                        </select>
                    </div>
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
                                class="{{ $field }} min-h-[44px]">
                        </div>
                    @endforeach
                    <x-input-error :messages="$errors->get('questions.'.$i.'.options')" />
                </div>
                <div x-show="type === 'true_false'" class="flex gap-6">
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
                    <x-input-label :for="'q'.$i.'_explanation'" value="الشرح بعد الإجابة" />
                    <textarea id="q{{ $i }}_explanation" name="questions[{{ $i }}][explanation]" rows="2" class="{{ $field }}">{{ $question['explanation'] }}</textarea>
                </div>
            </fieldset>
        @endforeach

        <div class="flex gap-2">
            <x-primary-button>حفظ التحدي</x-primary-button>
            <a href="{{ route('admin.challenges.index') }}" class="btn-secondary">إلغاء</a>
        </div>
    </form>
</x-app-layout>
