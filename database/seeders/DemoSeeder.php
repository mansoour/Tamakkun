<?php

namespace Database\Seeders;

use App\Enums\FollowUpStatus;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Content;
use App\Models\CounselorProfile;
use App\Models\ExamAttempt;
use App\Models\Grade;
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
 * Local demo accounts only: one admin, one counselor and one student, in one
 * school with the three secondary grades (so self-registration can be tried).
 * Learning content, تحدي اليوم and دفعة اليوم are real and come from migrations.
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
            'name' => 'مديرة المنصة',
            'username' => 'admin',
            'email' => 'admin@tamakkun.test',
        ]);

        $school = School::create(['name' => 'مدرسة تمكّن الثانوية', 'city' => 'بيشة', 'is_active' => true]);
        $year = AcademicYear::create(['school_id' => $school->id, 'name' => '1447–1448', 'is_current' => true]);

        $classrooms = collect(['الأول الثانوي' => 10, 'الثاني الثانوي' => 11, 'الثالث الثانوي' => 12])
            ->flatMap(function (int $level, string $name) use ($year) {
                $grade = Grade::create(['academic_year_id' => $year->id, 'name' => $name, 'level' => $level, 'sort_order' => $level]);
                $prefix = $level - 9;

                return collect([1, 2])->map(fn (int $i) => Classroom::create([
                    'grade_id' => $grade->id, 'name' => "{$prefix}/{$i}", 'sort_order' => $i,
                ]));
            });

        $counselor = CounselorProfile::factory()->forSchool($school)->create([
            'user_id' => User::factory()->counselor()->create([
                'name' => 'نورة العتيبي',
                'username' => 'counselor',
                'email' => 'counselor@tamakkun.test',
            ])->id,
            'job_title' => 'الموجهة الطلابية',
        ])->user;

        $student = StudentProfile::factory()->inClassroom($classrooms->firstWhere('name', '3/1'))->assignedTo($counselor)->create([
            'user_id' => User::factory()->student()->create(['name' => 'سارة أحمد', 'username' => 'student'])->id,
            'student_code' => 'student',
        ])->user;

        $this->seedDemoProgress($student);
        $this->seedDemoExams($student);
        $this->seedDemoFollowUp($counselor);
        $this->seedDemoAnnouncement($counselor);
    }

    private function seedDemoAnnouncement(User $counselor): void
    {
        app(AnnouncementService::class)->create($counselor, [
            'title' => 'مرحبًا بكنّ في المنصة',
            'body' => "ابدئي بمسار القدرات الكمي من قسم «استراتيجيات الحل»، وأجيبي عن «تحدي اليوم» كل يوم.\nوسجّلي موعد اختبارك ودرجتك المستهدفة في «موعدي ودرجتي» لنتابع استعدادك معًا.",
            'audience' => 'my_students',
        ]);
    }

    /**
     * A follow-up status and two notes for the demo student, then the automatic alerts.
     */
    private function seedDemoFollowUp(User $counselor): void
    {
        $followUp = app(FollowUpService::class);
        $profile = $counselor->assignedStudents()->sole();

        $followUp->setStatus($profile, FollowUpStatus::WATCH);
        $followUp->addNote($profile, $counselor, 'تحتاج إلى تركيز أكبر على أسئلة المقارنة في القسم الكمي. متابعة خطتها الأسبوعية.', true);
        $followUp->addNote($profile, $counselor, 'أحسنتِ في الأسبوع الماضي! أكملي هذا الأسبوع دروس الهندسة وألعابها.', false);

        app(StudentAlertService::class)->refreshAll();
    }

    /**
     * Two Qudurat results and a booked Tahsili exam for the demo student.
     */
    private function seedDemoExams(User $student): void
    {
        ExamAttempt::factory()->withScore(70, 90)->create(['student_id' => $student->id, 'attempt_number' => 1, 'target_score' => 85]);
        ExamAttempt::factory()->withScore(76, 20)->create(['student_id' => $student->id, 'attempt_number' => 2]);
        ExamAttempt::factory()->booked(18)->create(['student_id' => $student->id, 'exam_type' => 'tahsili']);
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
