<?php

namespace App\Http\Requests\Student;

use App\Enums\ExamBookingStatus;
use App\Enums\ExamType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExamAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Route middleware enforces student-area.access; the attempt is always the student's own.
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'exam_type' => ['required', Rule::enum(ExamType::class)],
            'booking_status' => ['required', Rule::enum(ExamBookingStatus::class)],
            'exam_date' => ['nullable', 'required_unless:booking_status,not_booked', 'date', 'after_or_equal:2020-01-01', 'before:'.now()->addYears(2)->toDateString()],
            'score' => ['nullable', 'required_if:booking_status,result_received', 'integer', 'between:0,100'],
            'target_score' => ['nullable', 'integer', 'between:1,100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('exam_date')) {
                return;
            }

            $status = ExamBookingStatus::from($this->input('booking_status'));
            $date = Carbon::parse($this->input('exam_date'))->startOfDay();

            if ($status->isTaken() && $date->isFuture()) {
                $validator->errors()->add('exam_date', 'لا يمكن أن يكون الاختبار قد تمّ في تاريخ لم يأتِ بعد.');
            }
        }];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'exam_type' => 'نوع الاختبار', 'booking_status' => 'حالة الحجز', 'exam_date' => 'تاريخ الاختبار',
            'score' => 'الدرجة', 'target_score' => 'الدرجة المستهدفة', 'notes' => 'ملاحظات',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'exam_date.required_unless' => 'حدّدي تاريخ الاختبار بعد الحجز.',
            'score.required_if' => 'أدخلي الدرجة بعد ظهور النتيجة.',
        ];
    }
}
