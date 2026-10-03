# Quizzes (v0.9)

Brief §54. Short practice quizzes attached to learning content. Code: `App\Services\QuizService`, `Admin\QuizController`, `Student\QuizController`.

## For admins

1. Create a content item with the type **اختبار قصير** (`/admin/content/create`).
2. Open **الأسئلة** next to it in the content list (or **أسئلة الاختبار** on its edit page).
3. Add up to 20 questions: multiple choice (2 to 4 options) or true/false, an optional explanation, and the pass percentage (default 60%).
4. Publish the content as usual. A published quiz without questions shows «قريبًا» to students.

Saving the questions replaces them. Earlier attempts keep their scores, but their per-question answers are removed. The page warns about this when attempts exist. Each save is audited as `quiz.updated`.

Only users with `content.manage` can edit quizzes.

## For students

- The questions appear on the content page. Every question must be answered before submitting.
- The score shows at once: the number correct, the percentage, the best percentage so far and the pass mark. Each question then shows the student's answer, the correct one and the explanation.
- Reaching the pass percentage completes the content. Otherwise it stays in progress. Retakes are unlimited.
- Correct answers are never sent to the browser before submitting (`is_correct` is hidden and never rendered into the form).

## For counselors

The student detail page (**المحتوى** tab) lists the student's latest quiz attempts with their percentages.

## Later

`numeric` and `matching` question types (brief §54) are not built yet.
