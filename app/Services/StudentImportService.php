<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\AcademicYear;
use App\Models\School;
use App\Models\StudentImport;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\Username;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Bulk student import from CSV.
 *
 * Workflow: upload → parse + validate every row → preview (errors listed by
 * row) → confirm → queued import → summary. The import is all-or-nothing:
 * a file with any invalid row cannot be confirmed, and a failure during the
 * import rolls everything back. See docs/imports.md.
 */
class StudentImportService
{
    public const MAX_ROWS = 500;

    /**
     * Canonical column => accepted header spellings (English and Arabic).
     */
    public const COLUMNS = [
        'student_code' => ['student_code', 'رقم الطالبة', 'الرقم'],
        'name' => ['name', 'الاسم', 'اسم الطالبة'],
        'school' => ['school', 'المدرسة'],
        'grade' => ['grade', 'الصف'],
        'classroom' => ['classroom', 'الفصل'],
        'counselor' => ['counselor', 'الموجهة', 'الموجهة الطلابية'],
        'username' => ['username', 'اسم المستخدم'],
        'initial_password' => ['initial_password', 'كلمة المرور'],
    ];

    public const REQUIRED_COLUMNS = ['student_code', 'name', 'school', 'initial_password'];

    public function __construct(
        private readonly SchoolMembershipService $membership,
        private readonly AuditLogger $audit,
    ) {}

    public function preview(UploadedFile $file, ?User $uploader): StudentImport
    {
        $rows = [];
        $errors = [];

        try {
            $records = $this->readCsv($file->getRealPath());
            [$valid, $errors] = $this->validateRecords($records);
            $rows = $valid;
            $total = count($records);
        } catch (RuntimeException $e) {
            $errors = [['row' => null, 'messages' => [$e->getMessage()]]];
            $total = 0;
        }

        return StudentImport::create([
            'uploaded_by' => $uploader?->id,
            'original_filename' => Str::limit($file->getClientOriginalName(), 250, ''),
            'status' => ImportStatus::PREVIEWED,
            'total_rows' => $total,
            'payload' => $errors === [] ? $rows : null,
            'row_errors' => $errors,
        ]);
    }

    public function confirm(StudentImport $import): void
    {
        if ($import->status !== ImportStatus::PREVIEWED || $import->hasErrors()) {
            throw new RuntimeException('لا يمكن تأكيد هذا الاستيراد.');
        }

        $import->update(['status' => ImportStatus::QUEUED]);

        $this->audit->record('students.import_confirmed', $import, null, [
            'file' => $import->original_filename,
            'rows' => $import->total_rows,
        ]);
    }

    /**
     * Runs inside the queued job. All rows are created in one transaction.
     */
    public function run(StudentImport $import): void
    {
        if ($import->status !== ImportStatus::QUEUED) {
            return;
        }

        try {
            $created = DB::transaction(function () use ($import) {
                $rows = $import->payload ?? [];

                // Re-check uniqueness: accounts may have been added since the preview.
                $this->assertStillUnique($rows);

                foreach ($rows as $row) {
                    $this->membership->createStudent($row + ['status' => UserStatus::ACTIVE]);
                }

                return count($rows);
            });

            $import->update([
                'status' => ImportStatus::COMPLETED,
                'imported_rows' => $created,
                'payload' => null,
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            $import->update([
                'status' => ImportStatus::FAILED,
                'payload' => null,
                'error_message' => $e instanceof RuntimeException ? $e->getMessage() : 'حدث خطأ غير متوقع أثناء الاستيراد ولم تُحفظ أي بيانات.',
                'completed_at' => now(),
            ]);

            if (! $e instanceof RuntimeException) {
                report($e);
            }
        }
    }

    /**
     * Reads a CSV file into a list of associative rows keyed by canonical column.
     *
     * @return list<array<string, string>>
     */
    public function readCsv(string $path): array
    {
        $contents = (string) file_get_contents($path);
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        if (! mb_check_encoding($contents, 'UTF-8')) {
            // Excel on Arabic Windows saves "CSV" as Windows-1256.
            $converted = @iconv('CP1256', 'UTF-8//IGNORE', $contents);

            if ($converted === false || ! mb_check_encoding($converted, 'UTF-8')) {
                throw new RuntimeException('تعذّر قراءة ترميز الملف. احفظيه بصيغة CSV UTF-8 ثم أعيدي الرفع.');
            }

            $contents = $converted;
        }

        if (trim($contents) === '') {
            throw new RuntimeException('الملف فارغ.');
        }

        $firstLine = strtok($contents, "\r\n");
        $delimiter = collect([',', ';', "\t"])->sortByDesc(fn ($d) => substr_count($firstLine, $d))->first();

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        $header = fgetcsv($stream, null, $delimiter, '"', '');
        $map = $this->mapHeader($header ?: []);

        $records = [];

        while (($line = fgetcsv($stream, null, $delimiter, '"', '')) !== false) {
            if ($line === [null] || trim(implode('', $line)) === '') {
                continue;
            }

            $record = [];
            foreach ($map as $index => $column) {
                $record[$column] = trim((string) ($line[$index] ?? ''));
            }
            $records[] = $record;

            if (count($records) > self::MAX_ROWS) {
                fclose($stream);
                throw new RuntimeException('الحد الأقصى '.self::MAX_ROWS.' طالبة في الملف الواحد. قسّمي الملف ثم أعيدي الرفع.');
            }
        }

        fclose($stream);

        if ($records === []) {
            throw new RuntimeException('لا يحتوي الملف على أي صفوف بيانات.');
        }

        return $records;
    }

    /**
     * @param  list<string|null>  $header
     * @return array<int, string> column index => canonical column
     */
    private function mapHeader(array $header): array
    {
        $map = [];

        foreach ($header as $index => $title) {
            $title = mb_strtolower(trim((string) $title));

            foreach (self::COLUMNS as $column => $aliases) {
                if (in_array($title, $aliases, true)) {
                    $map[$index] = $column;
                }
            }
        }

        $missing = array_diff(self::REQUIRED_COLUMNS, $map);

        if ($missing !== []) {
            throw new RuntimeException('أعمدة مطلوبة غير موجودة في الملف: '.implode('، ', $missing));
        }

        return $map;
    }

    /**
     * @param  list<array<string, string>>  $records
     * @return array{0: list<array<string, mixed>>, 1: list<array{row: int, messages: list<string>}>}
     */
    private function validateRecords(array $records): array
    {
        $codes = array_count_values(array_filter(array_column($records, 'student_code')));
        $usernames = array_count_values(array_map(
            fn ($r) => mb_strtolower(($r['username'] ?? '') ?: $r['student_code']),
            $records,
        ));

        $existingCodes = StudentProfile::whereIn('student_code', array_keys($codes))->pluck('student_code')->all();
        $existingUsernames = User::whereIn('username', array_keys($usernames))->pluck('username')->map(fn ($u) => mb_strtolower($u))->all();

        $valid = [];
        $errors = [];

        foreach ($records as $i => $record) {
            $rowNumber = $i + 2; // +1 for the header, +1 for 1-based numbering
            $username = ($record['username'] ?? '') ?: $record['student_code'];

            $validator = Validator::make(
                ['student_code' => $record['student_code'], 'name' => $record['name'], 'username' => $username, 'initial_password' => $record['initial_password']],
                [
                    'student_code' => ['required', ...Username::rules(Username::CODE_MAX)],
                    'name' => ['required', 'string', 'max:255'],
                    'username' => ['required', ...Username::rules()],
                    'initial_password' => ['required', 'string', 'min:8', 'max:72'],
                ],
                [],
                ['student_code' => 'رقم الطالبة', 'name' => 'الاسم', 'username' => 'اسم المستخدم', 'initial_password' => 'كلمة المرور'],
            );

            $messages = $validator->errors()->all();

            if (($codes[$record['student_code']] ?? 0) > 1) {
                $messages[] = 'رقم الطالبة مكرر داخل الملف.';
            }
            if (in_array($record['student_code'], $existingCodes, true)) {
                $messages[] = 'رقم الطالبة مستخدم مسبقًا في النظام.';
            }
            if (($usernames[mb_strtolower($username)] ?? 0) > 1) {
                $messages[] = 'اسم المستخدم مكرر داخل الملف.';
            }
            if (in_array(mb_strtolower($username), $existingUsernames, true)) {
                $messages[] = 'اسم المستخدم مستخدم مسبقًا في النظام.';
            }

            [$placement, $placementErrors] = $this->resolvePlacement($record);
            $messages = [...$messages, ...$placementErrors];

            if ($messages !== []) {
                $errors[] = ['row' => $rowNumber, 'messages' => array_values(array_unique($messages))];

                continue;
            }

            $valid[] = [
                'name' => $record['name'],
                'username' => $username,
                'student_code' => $record['student_code'],
                'password' => $record['initial_password'],
                ...$placement,
            ];
        }

        return [$valid, $errors];
    }

    /**
     * Finds school, classroom (in the school's current academic year) and counselor.
     *
     * @param  array<string, string>  $record
     * @return array{0: array{school_id: int|null, classroom_id: int|null, counselor_id: int|null}, 1: list<string>}
     */
    private function resolvePlacement(array $record): array
    {
        $placement = ['school_id' => null, 'classroom_id' => null, 'counselor_id' => null];
        $errors = [];

        $school = School::where('name', $record['school'])->first();

        if ($school === null) {
            return [$placement, ["المدرسة «{$record['school']}» غير موجودة."]];
        }

        $placement['school_id'] = $school->id;

        $gradeName = $record['grade'] ?? '';
        $classroomName = $record['classroom'] ?? '';

        if ($classroomName !== '' || $gradeName !== '') {
            $year = AcademicYear::where('school_id', $school->id)->where('is_current', true)->first();
            $grade = $year?->grades()->where('name', $gradeName)->first();
            $classroom = $grade?->classrooms()->where('name', $classroomName)->first();

            match (true) {
                $year === null => $errors[] = 'لا يوجد عام دراسي حالي لهذه المدرسة.',
                $grade === null => $errors[] = "الصف «{$gradeName}» غير موجود في العام الدراسي الحالي.",
                $classroom === null => $errors[] = "الفصل «{$classroomName}» غير موجود في هذا الصف.",
                default => $placement['classroom_id'] = $classroom->id,
            };
        }

        $counselorKey = $record['counselor'] ?? '';

        if ($counselorKey !== '') {
            $counselor = User::role(RoleName::COUNSELOR->value)
                ->where(fn ($q) => $q->where('username', $counselorKey)->orWhere('email', $counselorKey))
                ->whereHas('counselorProfile', fn ($q) => $q->where('school_id', $school->id))
                ->first();

            $counselor === null
                ? $errors[] = "الموجهة «{$counselorKey}» غير موجودة في هذه المدرسة."
                : $placement['counselor_id'] = $counselor->id;
        }

        return [$placement, $errors];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function assertStillUnique(array $rows): void
    {
        $codeTaken = StudentProfile::whereIn('student_code', array_column($rows, 'student_code'))->exists();
        $usernameTaken = User::whereIn('username', array_column($rows, 'username'))->exists();

        if ($codeTaken || $usernameTaken) {
            throw new RuntimeException('أُضيفت حسابات بنفس رقم الطالبة أو اسم المستخدم بعد المعاينة. أعيدي رفع الملف للتحقق من جديد.');
        }
    }
}
