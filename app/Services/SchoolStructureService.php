<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Grade;
use App\Models\School;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Create/update/delete for schools, academic years, grades and classrooms,
 * with audit logging and safe deletion (a level with children or students
 * cannot be deleted).
 */
class SchoolStructureService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @template TModel of Model
     *
     * @param  class-string<TModel>  $class
     * @param  array<string, mixed>  $data
     * @return TModel
     */
    public function create(string $class, array $data): Model
    {
        return DB::transaction(function () use ($class, $data) {
            $model = $class::create($data);
            $this->keepSingleCurrentYear($model);
            $this->audit->record($this->action($model, 'created'), $model, null, $model->only(array_keys($data)));

            return $model;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        return DB::transaction(function () use ($model, $data) {
            $model->fill($data);
            $changes = $model->getDirty();
            $original = array_intersect_key($model->getOriginal(), $changes);
            $model->save();
            $this->keepSingleCurrentYear($model);

            if ($changes !== []) {
                $this->audit->record($this->action($model, 'updated'), $model, $original, $changes);
            }

            return $model;
        });
    }

    /**
     * @throws ValidationException when the record still has children
     */
    public function delete(Model $model): void
    {
        if ($reason = $this->blockingReason($model)) {
            throw ValidationException::withMessages(['delete' => $reason]);
        }

        $snapshot = $model->attributesToArray();
        $model->delete();
        $this->audit->record($this->action($model, 'deleted'), $model, $snapshot, null);
    }

    public function blockingReason(Model $model): ?string
    {
        return match (true) {
            $model instanceof School && $model->academicYears()->exists() => 'لا يمكن حذف المدرسة لوجود أعوام دراسية مرتبطة بها.',
            $model instanceof School && ($model->studentProfiles()->exists() || $model->counselorProfiles()->exists()) => 'لا يمكن حذف المدرسة لوجود حسابات مرتبطة بها.',
            $model instanceof AcademicYear && $model->grades()->exists() => 'لا يمكن حذف العام الدراسي لوجود صفوف مرتبطة به.',
            $model instanceof Grade && $model->classrooms()->exists() => 'لا يمكن حذف الصف لوجود فصول مرتبطة به.',
            $model instanceof Classroom && $model->studentProfiles()->exists() => 'لا يمكن حذف الفصل لوجود طالبات مسجلات فيه. انقلي الطالبات أولًا.',
            default => null,
        };
    }

    /**
     * Only one academic year per school may be marked current.
     */
    private function keepSingleCurrentYear(Model $model): void
    {
        if ($model instanceof AcademicYear && $model->is_current) {
            AcademicYear::query()
                ->where('school_id', $model->school_id)
                ->whereKeyNot($model->getKey())
                ->where('is_current', true)
                ->update(['is_current' => false]);
        }
    }

    private function action(Model $model, string $verb): string
    {
        $name = match (true) {
            $model instanceof School => 'school',
            $model instanceof AcademicYear => 'academic_year',
            $model instanceof Grade => 'grade',
            $model instanceof Classroom => 'classroom',
            default => 'record',
        };

        return "{$name}.{$verb}";
    }
}
