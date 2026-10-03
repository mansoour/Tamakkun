<?php

namespace App\Services;

use App\Enums\AnnouncementAudience;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Announcement;
use App\Models\StudentProfile;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/**
 * Announcements: targeting, visibility and notification dispatch.
 * Scheduled announcements are notified by the `tamakkun:dispatch-announcements`
 * command once their start time arrives.
 */
class AnnouncementService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{title: string, body: string, audience: string, target_id?: ?int, starts_at?: ?string, ends_at?: ?string}  $data
     */
    public function create(User $author, array $data): Announcement
    {
        $announcement = DB::transaction(function () use ($author, $data) {
            $audience = AnnouncementAudience::from($data['audience']);

            $announcement = Announcement::create([
                'title' => $data['title'], 'body' => $data['body'], 'author_id' => $author->id,
                'audience' => $audience, 'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null,
                'is_published' => true,
            ]);

            $target = match ($audience) {
                AnnouncementAudience::ALL => null,
                AnnouncementAudience::MY_STUDENTS => ['counselor', $author->id],
                AnnouncementAudience::CLASSROOM => ['classroom', (int) $data['target_id']],
                AnnouncementAudience::STUDENT => ['student', (int) $data['target_id']],
            };

            if ($target) {
                $announcement->targets()->create(['target_type' => $target[0], 'target_id' => $target[1]]);
            }

            $this->audit->record('announcement.created', $announcement, null, ['title' => $announcement->title, 'audience' => $audience->value]);

            return $announcement;
        });

        $this->dispatchIfDue($announcement);

        return $announcement;
    }

    public function withdraw(Announcement $announcement): void
    {
        $announcement->update(['is_published' => false]);
        $this->audit->record('announcement.withdrawn', $announcement);
    }

    /**
     * Current announcements a student should see.
     *
     * @return Builder<Announcement>
     */
    public function visibleTo(User $student): Builder
    {
        $profile = $student->studentProfile;

        return Announcement::current()->where(fn (Builder $q) => $q
            ->where('audience', AnnouncementAudience::ALL)
            ->orWhereHas('targets', fn ($t) => $t->where(fn ($t) => $t
                ->where(fn ($x) => $x->where('target_type', 'student')->where('target_id', $student->id))
                ->when($profile?->classroom_id, fn ($x, $id) => $x->orWhere(fn ($y) => $y->where('target_type', 'classroom')->where('target_id', $id)))
                ->when($profile?->counselor_id, fn ($x, $id) => $x->orWhere(fn ($y) => $y->where('target_type', 'counselor')->where('target_id', $id)))
            )))
            ->latest('starts_at')->latest('id');
    }

    /**
     * Active student accounts the announcement is addressed to.
     *
     * @return Builder<User>
     */
    public function recipients(Announcement $announcement): Builder
    {
        $target = $announcement->targets()->first();
        $students = User::role(RoleName::STUDENT->value)->where('status', UserStatus::ACTIVE);

        return match ($announcement->audience) {
            AnnouncementAudience::ALL => $students,
            AnnouncementAudience::STUDENT => $students->whereKey($target?->target_id),
            AnnouncementAudience::CLASSROOM => $students->whereIn('id', StudentProfile::where('classroom_id', $target?->target_id)->select('user_id')),
            AnnouncementAudience::MY_STUDENTS => $students->whereIn('id', StudentProfile::where('counselor_id', $target?->target_id)->select('user_id')),
        };
    }

    public function dispatchIfDue(Announcement $announcement): bool
    {
        $due = $announcement->is_published && $announcement->notified_at === null
            && ($announcement->starts_at === null || $announcement->starts_at->isPast())
            && ($announcement->ends_at === null || $announcement->ends_at->isFuture());

        if (! $due) {
            return false;
        }

        $this->recipients($announcement)->chunkById(200, fn ($users) => Notification::send($users, new AnnouncementPublished($announcement)));
        $announcement->update(['notified_at' => now()]);

        return true;
    }

    /**
     * Notifies scheduled announcements whose start time has arrived.
     */
    public function dispatchDue(): int
    {
        return Announcement::whereNull('notified_at')->current()->get()
            ->filter(fn (Announcement $a) => $this->dispatchIfDue($a))->count();
    }
}
