<?php

namespace Database\Seeders;

use App\Enums\FollowUpStatus;
use App\Enums\MotivationType;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Content;
use App\Models\CounselorProfile;
use App\Models\DailyChallenge;
use App\Models\ExamAttempt;
use App\Models\Grade;
use App\Models\Motivation;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\AnnouncementService;
use App\Services\ContentCompletionService;
use App\Services\FollowUpService;
use App\Services\StudentAlertService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Fake demo data for local development only.
 * Every name here is invented; never put real student data in Git.
 * All demo passwords are "password" (see README).
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder must never run in production.');
        }

        User::factory()->admin()->create([
            'name' => 'مديرة النظام (تجريبي)',
            'username' => 'admin',
            'email' => 'admin@tamakkun.test',
        ]);

        $school = School::create(['name' => 'المدرسة الثانوية التجريبية', 'city' => 'مدينة تجريبية', 'is_active' => true]);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '1447–1448', 'is_current' => true]);
        $grade = Grade::create(['academic_year_id' => $year->id, 'name' => 'الثالث الثانوي', 'level' => 12]);
        $classrooms = collect(['3/1', '3/2'])->map(fn ($name, $i) => Classroom::create([
            'grade_id' => $grade->id, 'name' => $name, 'sort_order' => $i,
        ]));

        $counselor = CounselorProfile::factory()->forSchool($school)->create([
            'user_id' => User::factory()->counselor()->create([
                'name' => 'الموجهة الطلابية (تجريبي)',
                'username' => 'counselor',
                'email' => 'counselor@tamakkun.test',
            ])->id,
        ])->user;

        $secondCounselor = CounselorProfile::factory()->forSchool($school)->create([
            'user_id' => User::factory()->counselor()->create([
                'name' => 'موجهة ثانية (تجريبي)',
                'username' => 'counselor2',
                'email' => 'counselor2@tamakkun.test',
            ])->id,
        ])->user;

        StudentProfile::factory()->inClassroom($classrooms[0])->assignedTo($counselor)->create([
            'user_id' => User::factory()->student()->create(['name' => 'طالبة تجريبية', 'username' => 'student'])->id,
            'student_code' => 'student',
        ]);

        StudentProfile::factory()->inClassroom($classrooms[0])->assignedTo($counselor)->create([
            'user_id' => User::factory()->student()->withStatus(UserStatus::DISABLED)
                ->create(['name' => 'طالبة بحساب معطّل (تجريبي)', 'username' => 'disabled-student'])->id,
            'student_code' => 'disabled-student',
        ]);

        foreach (range(1, 14) as $i) {
            StudentProfile::factory()
                ->inClassroom($classrooms[$i % 2])
                ->assignedTo($i % 2 ? $counselor : $secondCounselor)
                ->create();
        }

        // A few students without a counselor, to exercise the "unassigned" filter.
        StudentProfile::factory()->count(2)->inClassroom($classrooms[1])->create();

        $this->seedDemoProgress(User::where('username', 'student')->sole());
        $this->seedDemoExams();
        $this->seedDemoFollowUp($counselor);
        $this->seedDemoEngagement($counselor);
    }

    /**
     * Today's (fake) challenge, a few motivation items and an announcement.
     */
    private function seedDemoEngagement(User $counselor): void
    {
        DailyChallenge::factory()->create(['title' => 'تحدي تجريبي']);

        Motivation::factory()->create(['title' => 'مهمة 15 دقيقة', 'content' => 'اختاري مهارة واحدة، شاهدي شرحًا قصيرًا ثم حلي 5 أسئلة.']);
        Motivation::factory()->create(['title' => 'عادة مذاكرة', 'media_type' => MotivationType::STUDY_HABIT, 'content' => 'ذاكري في الوقت نفسه كل يوم ولو لمدة قصيرة.']);
        Motivation::factory()->create(['title' => 'نصيحة', 'media_type' => MotivationType::TIP, 'content' => 'اقرئي السؤال كاملًا قبل النظر إلى الخيارات.']);

        app(AnnouncementService::class)->create($counselor, [
            'title' => 'إعلان تجريبي', 'body' => 'هذا إعلان تجريبي من الموجهة الطلابية لطالباتها.', 'audience' => 'my_students',
        ]);
    }

    /**
     * A few follow-up statuses and notes, then generate the automatic alerts.
     */
    private function seedDemoFollowUp(User $counselor): void
    {
        $followUp = app(FollowUpService::class);
        $students = $counselor->assignedStudents()->with('user')->orderBy('id')->limit(3)->get();

        $followUp->setStatus($students[0], FollowUpStatus::WATCH);
        $followUp->addNote($students[0], $counselor, 'ملاحظة تجريبية خاصة: متابعة خطة المذاكرة الأسبوعية.', true);
        $followUp->addNote($students[0], $counselor, 'رسالة تجريبية: أحسنتِ في الأسبوع الماضي، استمري!', false);
        $followUp->setStatus($students[2], FollowUpStatus::NEEDS_FOLLOWUP);

        app(StudentAlertService::class)->refreshAll();
    }

    /**
     * Fake exam history: the demo student has two Qudurat results and a booked
     * Tahsili exam; other students get a mix of booked and unbooked states.
     */
    private function seedDemoExams(): void
    {
        $student = User::where('username', 'student')->sole();

        ExamAttempt::factory()->withScore(70, 90)->create(['student_id' => $student->id, 'attempt_number' => 1, 'target_score' => 85]);
        ExamAttempt::factory()->withScore(76, 20)->create(['student_id' => $student->id, 'attempt_number' => 2]);
        ExamAttempt::factory()->booked(18)->create(['student_id' => $student->id, 'exam_type' => 'tahsili']);

        User::role('student')->where('username', '!=', 'student')->get()->each(function (User $user, int $i) {
            match ($i % 3) {
                0 => ExamAttempt::factory()->booked(7 + $i)->create(['student_id' => $user->id]),
                1 => ExamAttempt::factory()->withScore(60 + $i)->create(['student_id' => $user->id]),
                default => null, // not booked yet
            };
        });
    }

    /**
     * Some completed and in-progress items so the demo student's dashboard is not empty.
     */
    private function seedDemoProgress(User $student): void
    {
        $completion = app(ContentCompletionService::class);
        $contents = Content::visible()->ordered()->limit(3)->get();

        $contents->take(2)->each(fn (Content $content) => $completion->complete($student, $content));

        if ($contents->count() === 3) {
            $completion->start($student, $contents[2]);
        }
    }
}
