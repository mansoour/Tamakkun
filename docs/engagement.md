# Engagement: challenge, motivation, announcements, notifications, badges

## تحدي اليوم (daily challenge)

- One challenge per date (Asia/Riyadh) with 1–3 questions. The default form offers one quantitative and one verbal question. Types are **multiple choice** (2–6 options) or **true/false**.
- Admins with `challenges.manage` create challenges at `/admin/challenges`. Once any student has answered a challenge, its questions are locked; it can only be unpublished.
- The student sees today's published challenge at `/student/challenge`. **One attempt per question.** After answering she sees whether she was right, the correct option, the explanation and her streak ("سلسلة أيامك الآن: N").
- The correct answer is never sent to the browser before she answers (`is_correct` is hidden from serialisation and not rendered).
- Answers are logged as `challenge_answered` activity and **count toward the learning streak**.
- Counselors see answer history and the correct-answer ratio in the student's **التحديات** tab.

## دفعة اليوم (motivation)

- Types: رسالة قصيرة (short message), مقطع (video), صورة (image), نصيحة (tip), مهمة 15 دقيقة (15-minute task), تذكير قبل الاختبار (pre-exam reminder), عادة مذاكرة (study habit).
- **Today's item:** an active item dated today wins. Otherwise a stable daily rotation over the active items without a date, so every student sees the same item all day. Items dated in the future are hidden until their date.
- Videos follow the same YouTube/Vimeo whitelist as content. Images go through `ImageOptimizer` (WebP).
- Managed at `/admin/motivations` (`motivations.manage`).

## Announcements (إعلانات)

| Author | Permission | Audiences |
|---|---|---|
| Admin | `announcements.manage-all` | all students, any classroom, any student |
| Counselor | `announcements.send` | her assigned students, a classroom that contains her students, one of her students |

- Optional `starts_at` and `ends_at`. Current announcements appear on the student dashboard (`#announcements`).
- Recipients get an in-app notification when the announcement starts: immediately, or via `tamakkun:dispatch-announcements` (every 5 minutes) for scheduled ones. Each announcement notifies **once** (`notified_at`).
- Withdrawing (`سحب`) hides an announcement. Every creation and withdrawal is audited.

## In-app notifications

Laravel database notifications, **queued** (`ShouldQueue`). Students see them at `/student/notifications`, with a bell and unread count in the header. Opening a notification marks it as read and follows its internal link only.

| Notification | When |
|---|---|
| `AnnouncementPublished` (رسالة جديدة) | An announcement starts |
| `DailyChallengeAvailable` (لديك تحدي جديد) | `tamakkun:send-reminders` at 07:00 when today's challenge is published (once per challenge) |
| `ExamReminder` (اختبار … بعد 7 أيام / غدًا) | `tamakkun:send-reminders` for booked exams 7 days and 1 day away (once per attempt per reminder, de-duplicated by a key) |

Email delivery of notifications arrives with Resend in v0.8.

## Badges (أوسمتي)

Personal and positive, computed from real data (`BadgeService`), and shown on تقدمي (the progress page) only while the admin setting **تفعيل الأوسمة** (`enable_gamification`) is on. **No public ranking.**

| Badge | Rule |
|---|---|
| 7 أيام متواصلة | Learning streak ≥ 7 |
| 10 دروس مكتملة | ≥ 10 completed items |
| أول اختبار | Any exam result received |
| تحسن 10 درجات | Improvement ≥ 10 in either exam |
| إكمال مرحلة التأسيس | All visible foundation-stage content completed |

## Seeded تحدي اليوم and دفعة اليوم (migration `2026_10_16_000001`)

- **20 challenges** in `database/data/daily-challenges.php`. Each has three multiple-choice questions: القدرات الكمي, القدرات اللفظي and التحصيلي (رياضيات). Every question carries a worked explanation and follows the skills and chapters of the imported content (النسبة المئوية, الهندسة, أسئلة المقارنة, التناظر اللفظي, المفردة الشاذة, الخطأ السياقي, المصفوفات, المتتابعات, اللوغاريتمات, النهايات…). They are scheduled one per day starting on the day the migration runs, skipping dates that already have a challenge. After the 20th day, add new challenges in the admin area.
- **16 motivation items** in `database/data/motivations.php` (messages, tips, study habits, 15-minute tasks and pre-exam reminders), shown in the daily rotation. They contain general study advice only: exam rules and dates must come from the official source.
- Both imports match on title, so re-running never duplicates anything or overwrites admin edits. They are skipped while unit tests run. `EngagementContentImportTest` runs them explicitly.

## لوحة الشرف (public leaderboard)

`/leaderboard`, in the public navbar. Points: 10 per completed content item + 5 per correct تحدي اليوم answer, for active students only, in two tabs: this month and all time (`LeaderboardService`, cached 10 minutes, top 10). For the students' privacy it shows only the first name, the initial of the family name (skipping «ال») and the grade: never the school, username or full name. Admins can hide it with **الإعدادات ← إظهار «لوحة الشرف» للزوار** (`show_leaderboard`).
