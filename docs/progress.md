# Student progress

All progress numbers come from `App\Services\StudentProgressService`. No page calculates percentages on its own.

## How progress is recorded

| Action | Button | Effect |
|---|---|---|
| Start | **ابدأ** | `not_started` → `in_progress`, sets `started_at`, logs `content_started` (first time only) |
| Complete | **أنجزت** | → `completed`, `completed_at`, 100%, logs `content_completed` (once). Starting is implicit. |
| Undo | **التراجع عن الإنجاز** | `completed` → `in_progress`, clears `completed_at`, logs `content_uncompleted` |
| Pass a quiz (v0.9) | **إرسال الإجابات** | A quiz content item is completed only by reaching its pass percentage; a failed attempt marks it in progress. «أنجزت» is refused for quizzes. Each submission logs `quiz_submitted`, which counts as learning activity. See [quizzes.md](quizzes.md). |
| View the page | — | updates `last_viewed_at` of **existing** progress only |

Opening a content page or its external link **never** starts or completes anything. Only the student's explicit buttons do. Actions work only on visible (published, not archived) content and always apply to the signed-in student.

## Formulas

| Figure | Formula |
|---|---|
| Overall completion % | `floor(completed visible items ÷ visible items × 100)`. 0 when there is no content |
| Per-section % | Same formula, limited to quantitative, verbal or tahsili |
| Weekly | Items completed since the start of the week (**Sunday 00:00**, Asia/Riyadh) ÷ the `weekly_content_goal` setting (default 6, admin-editable), capped at 100% |
| Streak | Consecutive days, ending today **or yesterday**, with at least one learning action (start or complete). Logging in alone does not count. |
| In progress | Items with status `in_progress` |
| Last activity | Time of the latest learning action |

"Visible" means published, not archived, and with a publish date that has passed. If content is archived after being completed, it drops out of both the numerator and the denominator.

## Where it appears

- **Student dashboard:** the summary line, progress cards (`<x-student-progress-card>`) and "أكملي من حيث توقفتِ" (continue where you left off).
- **تقدمي** (`/student/progress`): progress cards, per-section bars (`<x-progress-bar>`), the weekly goal, in-progress items and recent completions.
- **Content cards:** a status badge (قيد التقدم for in progress, مكتمل for completed).
- **Counselor student page:** the same progress cards and last learning activity.

## Favorites

The **أضيفي للمفضلة** (add to favorites) button toggles a row in `favorites`. **المفضلة** (`/student/favorites`) lists only favorites that are still visible.

## Activity logs (privacy)

`activity_logs` records only `login`, `content_started`, `content_completed` and `content_uncompleted`, with the content as subject and no extra metadata. Page views, time on page and clicks on external links are **not** recorded. The challenge and exam events planned for later versions will be added to `App\Enums\ActivityEvent` explicitly.
