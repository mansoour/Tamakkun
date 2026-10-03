<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContentSection;
use App\Enums\QuestionType;
use App\Models\DailyChallenge;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Daily challenge with 1–3 questions. Multiple-choice questions need 2–6
 * non-empty options and a correct one; true/false uses fixed options.
 */
class StoreChallengeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route middleware enforces challenges.manage.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'challenge_date' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_published' => ['boolean'],
            'questions' => ['required', 'array', 'min:1', 'max:3'],
            'questions.*.section' => ['required', Rule::enum(ContentSection::class)],
            'questions.*.question_type' => ['required', Rule::enum(QuestionType::class)],
            'questions.*.prompt' => ['required', 'string', 'max:2000'],
            'questions.*.explanation' => ['nullable', 'string', 'max:2000'],
            'questions.*.options' => ['array', 'max:6'],
            'questions.*.options.*' => ['nullable', 'string', 'max:500'],
            'questions.*.correct' => ['required', 'integer', 'min:0', 'max:5'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            // Compared by date (not raw value) so it works on every database driver.
            if ($this->filled('challenge_date') && ! $validator->errors()->has('challenge_date')
                && DailyChallenge::whereDate('challenge_date', $this->date('challenge_date'))
                    ->whereKeyNot($this->route('challenge')?->getKey())->exists()) {
                $validator->errors()->add('challenge_date', 'يوجد تحدٍّ آخر في هذا التاريخ.');
            }

            foreach ((array) $this->input('questions', []) as $i => $question) {
                if (($question['question_type'] ?? null) !== QuestionType::MULTIPLE_CHOICE->value) {
                    if ((int) ($question['correct'] ?? -1) > 1) {
                        $validator->errors()->add("questions.{$i}.correct", 'اختاري «صح» أو «خطأ».');
                    }

                    continue;
                }

                $options = array_values(array_filter((array) ($question['options'] ?? []), fn ($o) => trim((string) $o) !== ''));

                if (count($options) < 2) {
                    $validator->errors()->add("questions.{$i}.options", 'أضيفي خيارين على الأقل.');
                } elseif ((int) ($question['correct'] ?? -1) >= count($options)) {
                    $validator->errors()->add("questions.{$i}.correct", 'الإجابة الصحيحة يجب أن تكون أحد الخيارات المعبأة.');
                }
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'challenge_date' => 'تاريخ التحدي', 'title' => 'العنوان', 'questions' => 'الأسئلة',
            'questions.*.section' => 'القسم', 'questions.*.question_type' => 'نوع السؤال',
            'questions.*.prompt' => 'نص السؤال', 'questions.*.explanation' => 'الشرح', 'questions.*.correct' => 'الإجابة الصحيحة',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_published' => $this->boolean('is_published')]);
    }
}
