# Counselor follow-up and alerts

## Two independent signals

| Signal | Who sets it | Values |
|---|---|---|
| **Follow-up status** (`student_profiles.follow_up_status`) | The counselor, manually | طبيعي (normal), تحت الملاحظة (watch), تحتاج متابعة (needs follow-up), تم التواصل (contacted), تمت المعالجة (resolved) |
| **Automatic alerts** (`student_alerts`) | `StudentAlertService` | see below |

A student "needs follow-up" on the dashboard and follow-up page when she has any unresolved **warning or critical** alert, or her manual status is *تحت الملاحظة* or *تحتاج متابعة*.

## Automatic alerts

All thresholds are admin settings (`config/tamakkun.php` defaults).

| Type | Condition | Severity | Identity (context key) |
|---|---|---|---|
| `not_booked` (لم تحجز) | Grade 12 (classroom grade level 12, or no classroom) and no attempt of that exam type beyond "not booked" | warning | exam type |
| `upcoming_low_activity` (اختبار قريب ونشاط منخفض) | A booked exam within `upcoming_exam_alert_days` (default 14) **and** fewer than `low_activity_threshold` (default 2) learning actions in the last 7 days | critical | exam type + attempt + week |
| `inactive` (غير نشطة) | No learning action for `inactivity_days` (default 7). A student who never studied counts once her account is that old | warning | date of last activity |
| `improvement` (تحسّن في الدرجة) | Latest received score is higher than the previous one | positive | exam type + attempt |
| `below_target` (دون الهدف والاختبار قريب) | Best score is below target **and** a booked exam is within `upcoming_exam_alert_days` | warning | exam type + attempt |

### Lifecycle

- **Refresh:** daily at 05:30 (`tamakkun:refresh-alerts`, scheduled in `routes/console.php`) and immediately after a student changes an exam attempt. It can also be run manually with `php artisan tamakkun:refresh-alerts`.
- **No duplicates:** an alert is created only if none exists with the same student, type and context key.
- **Auto-resolve:** unresolved alerts whose condition no longer holds are closed by the system (`resolved_by` = null).
- **Counselor actions:** *اطّلعت* (acknowledged) and *تمت المعالجة* (resolved) are both audited. A counselor-resolved alert is **not recreated** while the same condition persists. A new occurrence, such as a new inactivity period, a new attempt or a new week, has a new key and creates a new alert.

## Notes

- Notes are **private by default**. The counselor ticks *مشاركة الملاحظة مع الطالبة* (share with the student) to make one visible. Shared notes appear on the student's dashboard under *رسائل من الموجهة الطلابية* (messages from the counselor).
- Private notes are never sent to student pages, and students cannot reach any counselor route.
- Only the author can delete a note. Audit entries record that a note was created or deleted, **never its text**.

## Counselor pages

| Route | Page |
|---|---|
| `/counselor/dashboard` | KPIs: عدد الطالبات (students), متوسط الإنجاز (average completion), تحتاج متابعة (needs follow-up), الاختبارات القادمة (upcoming exams within the alert window), متوسط التحسن (average improvement across both exams), لم يحجزن (not booked), غير نشطات (inactive). Also the needs-follow-up list, upcoming exams and latest alerts. |
| `/counselor/students` | Roster with all columns from brief §57, plus filters: search, class, booking, next exam type, activity, completion band, follow-up, best Qudurat below N |
| `/counselor/students/{id}` | Header summary and tabs: نظرة عامة (overview), الدرجات (scores), الاختبارات (exams), الأنشطة (activity), المحتوى (content), التحديات (challenges, coming in v0.7), الملاحظات (notes), التنبيهات (alerts) |
| `/counselor/follow-up` | Students needing follow-up |
| `/counselor/alerts` | Alerts filtered by status, severity and type |
| `/counselor/exams` | Upcoming booked exams, plus students who are not booked |
| `/counselor/results` | Latest and best scores, sorted by Qudurat improvement |

Counselors see their **assigned** students only. A user holding `students.view-all` sees everyone (`StudentRosterService::scopeFor`). Follow-up actions additionally require `students.follow-up`.
