<?php

namespace Tests\Feature\Counselor;

use App\Models\CounselorProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AssignedStudentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_counselor_sees_only_assigned_students(): void
    {
        $counselor = CounselorProfile::factory()->create()->user;
        StudentProfile::factory()->assignedTo($counselor)->create(['user_id' => User::factory()->student()->create(['name' => 'طالبتي'])->id]);
        StudentProfile::factory()->create(['user_id' => User::factory()->student()->create(['name' => 'طالبة أخرى'])->id]);

        $this->actingAs($counselor)->get('/counselor/students')
            ->assertOk()
            ->assertSee('طالبتي')
            ->assertDontSee('طالبة أخرى');
    }

    public function test_counselor_can_view_assigned_student(): void
    {
        $counselor = CounselorProfile::factory()->create()->user;
        $student = StudentProfile::factory()->assignedTo($counselor)->create();

        $this->actingAs($counselor)->get("/counselor/students/{$student->id}")->assertOk()->assertSee($student->user->name);
    }

    public function test_counselor_cannot_view_unassigned_student(): void
    {
        $counselor = CounselorProfile::factory()->create()->user;
        $student = StudentProfile::factory()->create();

        $this->actingAs($counselor)->get("/counselor/students/{$student->id}")->assertForbidden();
    }

    public function test_students_cannot_open_counselor_student_pages(): void
    {
        $student = StudentProfile::factory()->create();

        $this->actingAs($student->user)->get('/counselor/students')->assertForbidden();
        $this->actingAs($student->user)->get("/counselor/students/{$student->id}")->assertForbidden();
    }

    public function test_view_all_permission_allows_any_student(): void
    {
        $admin = User::factory()->admin()->create();
        $student = StudentProfile::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $student));
    }

    public function test_counselor_dashboard_counts_assigned_students(): void
    {
        $counselor = CounselorProfile::factory()->create()->user;
        StudentProfile::factory()->count(2)->assignedTo($counselor)->create();
        StudentProfile::factory()->create();

        $this->actingAs($counselor)->get('/counselor/dashboard')
            ->assertOk()
            ->assertViewHas('kpis', fn ($k) => $k['students'] === 2);
    }
}
