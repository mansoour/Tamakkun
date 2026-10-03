<?php

namespace App\Http\Controllers\Shared;

use App\Enums\AnnouncementAudience;
use App\Enums\PermissionName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Models\Classroom;
use App\Services\AnnouncementService;
use App\Services\StudentRosterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Announcements for both areas: admins (/admin/announcements, all students)
 * and counselors (/counselor/announcements, their students). The area is
 * taken from the route name.
 */
class AnnouncementController extends Controller
{
    public function __construct(private readonly AnnouncementService $announcements) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('shared.announcements.index', [
            'area' => $this->area($request),
            'announcements' => Announcement::with('targets', 'author')
                ->unless($user->can(PermissionName::ANNOUNCE_TO_ALL->value), fn ($q) => $q->where('author_id', $user->id))
                ->latest()->paginate(20),
        ]);
    }

    public function create(Request $request, StudentRosterService $roster): View
    {
        $user = $request->user();
        $all = $user->can(PermissionName::ANNOUNCE_TO_ALL->value);
        $students = $roster->scopeFor($user)->with('user')->get();

        return view('shared.announcements.form', [
            'area' => $this->area($request),
            'audiences' => $all
                ? [AnnouncementAudience::ALL, AnnouncementAudience::CLASSROOM, AnnouncementAudience::STUDENT]
                : [AnnouncementAudience::MY_STUDENTS, AnnouncementAudience::CLASSROOM, AnnouncementAudience::STUDENT],
            'classrooms' => Classroom::with('grade.academicYear.school')
                ->unless($all, fn ($q) => $q->whereIn('id', $students->pluck('classroom_id')->filter()))
                ->get()->mapWithKeys(fn (Classroom $c) => [$c->id => $c->fullName()]),
            'students' => $students->mapWithKeys(fn ($p) => [$p->user_id => $p->user->name.' ('.$p->student_code.')'])->sort(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $this->announcements->create($request->user(), $request->validated());

        return to_route($this->area($request).'.announcements.index')->with('success', 'تم إرسال الإعلان.');
    }

    public function withdraw(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->author_id === $request->user()->id
            || $request->user()->can(PermissionName::ANNOUNCE_TO_ALL->value), 403);

        $this->announcements->withdraw($announcement);

        return back()->with('success', 'تم سحب الإعلان.');
    }

    private function area(Request $request): string
    {
        return str_starts_with((string) $request->route()->getName(), 'admin.') ? 'admin' : 'counselor';
    }
}
