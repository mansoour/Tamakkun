<?php

namespace Database\Seeders;

use App\Enums\ContentSection;
use App\Enums\ContentStage;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Classroom;
use App\Models\Content;
use App\Models\CounselorProfile;
use App\Models\Grade;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
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

        $this->seedDemoContent();
    }

    /**
     * Clearly labelled demo lessons in the stakeholder's real categories.
     * No external URLs are invented; demo items carry text only.
     */
    private function seedDemoContent(): void
    {
        $stages = ContentStage::cases();

        foreach ([ContentSection::QUANTITATIVE, ContentSection::VERBAL] as $section) {
            Category::where('section', $section)->ordered()->limit(3)->get()->each(function (Category $category) use ($stages) {
                foreach (range(0, 1) as $i) {
                    Content::factory()->forCategory($category)->create([
                        'title' => "درس تجريبي: {$category->name} ".($i + 1),
                        'description' => 'محتوى تجريبي لأغراض العرض فقط.',
                        'body' => "هذا نص تجريبي.\nسيُستبدل بمحتوى حقيقي من مصدر مصرّح به.",
                        'stage' => $stages[$i],
                        'duration_seconds' => 600,
                    ]);
                }
            });
        }

        $math = Subject::where('slug', 'الرياضيات')->first();

        if ($math) {
            $chapter = Chapter::create(['subject_id' => $math->id, 'name' => 'باب تجريبي: الدوال']);
            $topic = Topic::create(['chapter_id' => $chapter->id, 'name' => 'موضوع تجريبي: مجال الدالة']);
            Content::factory()->tahsili($math)->create([
                'title' => 'درس تجريبي: مجال الدالة',
                'chapter_id' => $chapter->id,
                'topic_id' => $topic->id,
                'description' => 'محتوى تجريبي لأغراض العرض فقط.',
            ]);
        }
    }
}
