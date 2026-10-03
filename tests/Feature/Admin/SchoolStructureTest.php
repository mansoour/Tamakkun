<?php

namespace Tests\Feature\Admin;

use App\Enums\PermissionName;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolStructureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_can_list_and_create_schools(): void
    {
        $this->actingAs($this->admin)->get('/admin/schools')->assertOk()->assertSee('لا توجد مدارس بعد');

        $this->actingAs($this->admin)
            ->post('/admin/schools', ['name' => 'مدرسة الاختبار', 'city' => 'مدينة', 'is_active' => '1'])
            ->assertRedirect('/admin/schools');

        $this->assertDatabaseHas('schools', ['name' => 'مدرسة الاختبار', 'is_active' => true]);
        $this->assertSame('school.created', AuditLog::sole()->action);
    }

    public function test_school_names_must_be_unique(): void
    {
        School::factory()->create(['name' => 'مكررة']);

        $this->actingAs($this->admin)->post('/admin/schools', ['name' => 'مكررة'])->assertSessionHasErrors('name');
    }

    public function test_admin_can_update_a_school_and_the_change_is_audited(): void
    {
        $school = School::factory()->create(['name' => 'قديم']);

        $this->actingAs($this->admin)
            ->put("/admin/schools/{$school->id}", ['name' => 'جديد', 'is_active' => '0'])
            ->assertRedirect('/admin/schools');

        $this->assertSame('جديد', $school->fresh()->name);
        $this->assertFalse($school->fresh()->is_active);

        $log = AuditLog::where('action', 'school.updated')->sole();
        $this->assertSame('قديم', $log->old_values['name']);
        $this->assertSame('جديد', $log->new_values['name']);
    }

    public function test_school_with_academic_years_cannot_be_deleted(): void
    {
        $year = AcademicYear::factory()->create();

        $this->actingAs($this->admin)
            ->delete("/admin/schools/{$year->school_id}")
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($year->school);
    }

    public function test_empty_school_can_be_deleted(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->admin)->delete("/admin/schools/{$school->id}")->assertRedirect('/admin/schools');

        $this->assertModelMissing($school);
        $this->assertDatabaseHas('audit_logs', ['action' => 'school.deleted']);
    }

    public function test_only_one_academic_year_per_school_is_current(): void
    {
        $old = AcademicYear::factory()->create(['is_current' => true]);

        $this->actingAs($this->admin)->post('/admin/academic-years', [
            'school_id' => $old->school_id, 'name' => 'جديد', 'is_current' => '1',
        ])->assertRedirect('/admin/academic-years');

        $this->assertFalse($old->fresh()->is_current);
        $this->assertTrue(AcademicYear::where('name', 'جديد')->sole()->is_current);
    }

    public function test_academic_year_end_must_be_after_start(): void
    {
        $school = School::factory()->create();

        $this->actingAs($this->admin)->post('/admin/academic-years', [
            'school_id' => $school->id, 'name' => 'x', 'starts_on' => '2026-09-01', 'ends_on' => '2026-01-01',
        ])->assertSessionHasErrors('ends_on');
    }

    public function test_admin_can_create_grades_and_classrooms(): void
    {
        $year = AcademicYear::factory()->create();

        $this->actingAs($this->admin)->post('/admin/grades', [
            'academic_year_id' => $year->id, 'name' => 'الثالث الثانوي', 'level' => 12,
        ])->assertRedirect('/admin/grades');

        $grade = Grade::sole();

        $this->actingAs($this->admin)->post('/admin/classes', [
            'grade_id' => $grade->id, 'name' => '3/1',
        ])->assertRedirect('/admin/classes');

        $this->assertDatabaseHas('classrooms', ['grade_id' => $grade->id, 'name' => '3/1']);
        $this->actingAs($this->admin)->get('/admin/classes')->assertOk()->assertSee('3/1');
    }

    public function test_classroom_names_are_unique_within_a_grade(): void
    {
        $classroom = Classroom::factory()->create(['name' => '3/1']);

        $this->actingAs($this->admin)
            ->post('/admin/classes', ['grade_id' => $classroom->grade_id, 'name' => '3/1'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->admin)
            ->post('/admin/classes', ['grade_id' => Grade::factory()->create()->id, 'name' => '3/1'])
            ->assertSessionHasNoErrors();
    }

    public function test_classroom_with_students_cannot_be_deleted(): void
    {
        $classroom = Classroom::factory()->create();
        StudentProfile::factory()->inClassroom($classroom)->create();

        $this->actingAs($this->admin)
            ->delete("/admin/classes/{$classroom->id}")
            ->assertSessionHasErrors('delete');

        $this->assertModelExists($classroom);
    }

    public function test_counselors_and_students_cannot_manage_schools(): void
    {
        foreach ([User::factory()->counselor()->create(), User::factory()->student()->create()] as $user) {
            $this->actingAs($user)->get('/admin/schools')->assertForbidden();
            $this->actingAs($user)->post('/admin/schools', ['name' => 'x'])->assertForbidden();
        }

        $this->assertDatabaseCount('schools', 0);
    }

    public function test_admin_area_access_alone_does_not_allow_managing_schools(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(PermissionName::ACCESS_ADMIN_AREA->value);

        $this->actingAs($user)->get('/admin/dashboard')->assertOk();
        $this->actingAs($user)->get('/admin/schools')->assertForbidden();
    }
}
