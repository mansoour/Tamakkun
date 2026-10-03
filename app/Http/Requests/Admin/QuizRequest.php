<?php

namespace App\Http\Requests\Admin;

use App\Enums\QuestionType;
use App\Services\QuizService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route middleware enforces content.manage.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pass_percentage' => ['required', 'integer', 'between:1,100'],
            'questions' => ['required', 'array', 'min:1', 'max:'.QuizService::MAX_QUESTIONS],
            'questions.*.question_type' => ['required', Rule::enum(QuestionType::class)],
            'questions.*.prompt' => ['required', 'string', 'max:2000'],
            'questions.*.explanation' => ['nullable', 'string', 'max:2000'],
            'questions.*.options' => ['array', 'max:6'],
            'questions.*.options.*' => ['nullable', 'string', 'max:500'],
            'questions.*.correct' => ['required', 'integer', 'min:0', 'max:5'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator) {
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
            'pass_percentage' => 'نسبة النجاح', 'questions' => 'الأسئلة',
            'questions.*.question_type' => 'نوع السؤال', 'questions.*.prompt' => 'نص السؤال',
            'questions.*.explanation' => 'الشرح', 'questions.*.correct' => 'الإجابة الصحيحة',
        ];
    }
}
