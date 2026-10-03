<?php

namespace Tests\Feature\Admin;

use App\Enums\ImportStatus;
use App\Models\Classroom;
use App\Models\CounselorProfile;
use App\Models\StudentImport;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Classroom $classroom;

    private string $schoolName;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->classroom = Classroom::factory()->create(['name' => '3/1']);
        $this->schoolName = $this->classroom->grade->academicYear->school->name;
        CounselorProfile::factory()->forSchool($this->classroom->grade->academicYear->school)
            ->create(['user_id' => User::factory()->counselor()->create(['username' => 'c.sara'])->id]);
    }

    private function csv(string $contents, string $name = 'students.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $contents);
    }

    private function validCsv(): string
    {
        $grade = $this->classroom->grade->name;

        return "student_code,name,school,grade,classroom,counselor,username,initial_password\n"
            ."S1,طالبة أولى,{$this->schoolName},{$grade},3/1,c.sara,,password1\n"
            ."S2,طالبة ثانية,{$this->schoolName},{$grade},3/1,,s.two,password2\n";
    }

    public function test_valid_file_is_previewed_then_imported_after_confirmation(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/imports/students', ['file' => $this->csv($this->validCsv())]);

        $import = StudentImport::sole();
        $response->assertRedirect("/admin/imports/students/{$import->id}");
        $this->assertSame(ImportStatus::PREVIEWED, $import->status);
        $this->assertDatabaseCount('student_profiles', 0);

        $this->actingAs($this->admin)->get("/admin/imports/students/{$import->id}")
            ->assertOk()->assertSee('الملف صالح')->assertSee('طالبة أولى');

        $this->actingAs($this->admin)->post("/admin/imports/students/{$import->id}/confirm")
            ->assertRedirect("/admin/imports/students/{$import->id}");

        $import->refresh();
        $this->assertSame(ImportStatus::COMPLETED, $import->status);
        $this->assertSame(2, $import->imported_rows);
        $this->assertNull($import->payload, 'passwords are not kept after import');

        $first = StudentProfile::where('student_code', 'S1')->sole();
        $this->assertSame($this->classroom->id, $first->classroom_id);
        $this->assertSame('c.sara', $first->counselor->username);
        $this->assertSame('S1', $first->user->username);
        $this->assertSame('s.two', StudentProfile::where('student_code', 'S2')->sole()->user->username);
        $this->assertDatabaseHas('audit_logs', ['action' => 'students.import_confirmed']);

        $this->post('/logout');
        $this->post('/login', ['login' => 's.two', 'password' => 'password2']);
        $this->assertAuthenticated();
    }

    public function test_arabic_headers_and_excel_bom_are_supported(): void
    {
        $csv = "\xEF\xBB\xBF"."رقم الطالبة,الاسم,المدرسة,كلمة المرور\nS9,طالبة,{$this->schoolName},password9\n";

        $this->actingAs($this->admin)->post('/admin/imports/students', ['file' => $this->csv($csv)]);

        $this->assertFalse(StudentImport::sole()->hasErrors());
    }

    public function test_windows_1256_files_are_converted(): void
    {
        $csv = iconv('UTF-8', 'CP1256', "student_code,name,school,initial_password\nS9,طالبة,{$this->schoolName},password9\n");

        $this->actingAs($this->admin)->post('/admin/imports/students', ['file' => $this->csv($csv)]);

        $import = StudentImport::sole();
        $this->assertFalse($import->hasErrors(), json_encode($import->row_errors, JSON_UNESCAPED_UNICODE));
        $this->assertSame('طالبة', $import->payload[0]['name']);
    }

    public function test_semicolon_delimited_files_are_supported(): void
    {
        $csv = "student_code;name;school;initial_password\nS9;طالبة;{$this->schoolName};password9\n";

        $this->actingAs($this->admin)->post('/admin/imports/students', ['file' => $this->csv($csv)]);

        $this->assertFalse(StudentImport::sole()->hasErrors());
    }

    public function test_row_errors_are_reported_and_the_file_cannot_be_confirmed(): void
    {
        StudentProfile::factory()->create(['student_code' => 'TAKEN']);
        $grade = $this->classroom->grade->name;

        $csv = "student_code,name,school,grade,classroom,counselor,username,initial_password\n"
            ."TAKEN,أ,{$this->schoolName},,,,,password1\n"            // existing code
            ."DUP,ب,{$this->schoolName},,,,,password1\n"
            ."DUP,ج,{$this->schoolName},,,,,password1\n"               // duplicated in file
            ."S4,د,مدرسة غير موجودة,,,,,password1\n"                  // unknown school
            ."S5,هـ,{$this->schoolName},{$grade},9/9,,,password1\n"    // unknown classroom
            ."S6,و,{$this->schoolName},,,nobody,,password1\n"         // unknown counselor
            ."S7,ز,{$this->schoolName},,,,,short\n"                   // short password
            ."S8,ح,{$this->schoolName},,,,,password1\n";              // valid

        $this->actingAs($this->admin)->post('/admin/imports/students', ['file' => $this->csv($csv)]);

        $import = StudentImport::sole();
        $rows = collect($import->row_errors)->pluck('row')->all();
        $this->assertSame([2, 3, 4, 5, 6, 7, 8], $rows);
        $this->assertNull($import->payload);

        $this->actingAs($this->admin)->get("/admin/imports/students/{$import->id}")
            ->assertSee('لا يمكن الاستيراد')->assertSee('رقم الطالبة مستخدم مسبقًا في النظام.');

        $this->actingAs($this->admin)->post("/admin/imports/students/{$import->id}/confirm")->assertSessionHasErrors('import');
        $this->assertDatabaseCount('student_profiles', 1);
    }

    public function test_missing_required_columns_are_reported(): void
    {
        $this->actingAs($this->admin)->post('/admin/imports/students', ['file' => $this->csv("name,school\nأ,ب\n")]);

        $this->assertStringContainsString('student_code', StudentImport::sole()->row_errors[0]['messages'][0]);
    }

    public function test_import_rolls_back_completely_if_accounts_appeared_after_preview(): void
    {
        $this->actingAs($this->admin)->post('/admin/imports/students', ['file' => $this->csv($this->validCsv())]);
        $import = StudentImport::sole();

        // Someone creates S2 manually between preview and confirmation.
        StudentProfile::factory()->create(['student_code' => 'S2']);

        $this->actingAs($this->admin)->post("/admin/imports/students/{$import->id}/confirm");

        $this->assertSame(ImportStatus::FAILED, $import->fresh()->status);
        $this->assertFalse(StudentProfile::where('student_code', 'S1')->exists(), 'nothing is partially imported');
    }

    public function test_non_csv_files_are_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/admin/imports/students', ['file' => UploadedFile::fake()->image('photo.png')])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('student_imports', 0);
    }

    public function test_template_can_be_downloaded(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/imports/students/template');

        $response->assertOk()->assertDownload('tamakkun-students-template.csv');
        $this->assertStringContainsString('student_code,name,school', $response->streamedContent());
    }

    public function test_counselors_cannot_import_students(): void
    {
        $counselor = User::factory()->counselor()->create();

        $this->actingAs($counselor)->get('/admin/imports/students')->assertForbidden();
        $this->actingAs($counselor)->post('/admin/imports/students', ['file' => $this->csv($this->validCsv())])->assertForbidden();
    }
}
