# Exam tracking (موعدي ودرجتي)

Students record their own Qudurat (القدرات) and Tahsili (التحصيلي) attempts at `/student/exams`.

## Data

Each `exam_attempts` row has a type, an automatic **attempt number** (per student and type), a booking status, an exam date, a score, an optional target and notes.

| Booking status | Arabic | Date | Score |
|---|---|---|---|
| `not_booked` | لم تحجز | optional | — |
| `booked` | تم الحجز | **required** | — |
| `completed` | تم الاختبار | required, not in the future | — |
| `result_pending` | بانتظار النتيجة | required, not in the future | — |
| `result_received` | ظهرت النتيجة | required, not in the future | **required**, 0–100 |

A score is stored only with `result_received`; for other statuses it is cleared. The exam type of an existing attempt cannot change.

## Figures (`App\Services\ExamProgressService`)

| Figure | Rule |
|---|---|
| Latest score | Received score of the most recent attempt (by exam date, then attempt number) |
| Best score | Highest received score |
| Improvement | Latest minus the previous received score. Empty with fewer than two results |
| Target | The most recently set `target_score` for that exam type, otherwise the `default_target_score` setting (default 85), shown as "الهدف الافتراضي" (default target) |
| Gap to target | `max(0, target − best)` |
| Next exam | The earliest `booked` attempt dated today or later. The countdown uses Arabic forms (اليوم، غدًا، بعد يومين، بعد 5 أيام، بعد 18 يومًا) |
| Booked? | Any attempt whose status is not `not_booked`. Used for the "لم تحجز" (not booked) badge |

## Where it appears

- **Student:** موعدي ودرجتي (`<x-exam-countdown>`, `<x-score-summary>` per type, an attempts table with edit and delete), plus the next-exam and score cards on the dashboard.
- **Counselor:** the student page shows the same summaries and a read-only attempts table, for assigned students only.

## Authorization and audit

- `ExamAttemptPolicy`: only the owning student can update or delete. Users who may view the student (assigned counselor, or `students.view-all`) can view.
- Every create, change and delete writes `audit_logs` (`exam.created`, `exam.updated` with the changed fields only, `exam.deleted`) and a student `activity_logs` entry `exam_updated`.

## Not invented

Tamakkun does not hold exam dates, fees or policies. Students enter their own dates. Official booking and result services are reached through **روابط مهمة** (important links) once an admin has added verified links.
