<?php

namespace App\Services;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * لوحة الشرف: the most active students, public at /leaderboard.
 *
 * Points: 10 for each completed content item and 5 for each correct answer
 * to تحدي اليوم. Only active student accounts count. To protect students'
 * privacy the page shows the first name, the initial of the family name and
 * the grade only, never the school, username or full name. Admins can hide
 * the page with the `show_leaderboard` setting. See docs/engagement.md.
 */
class LeaderboardService
{
    public const POINTS_PER_COMPLETION = 10;

    public const POINTS_PER_CORRECT_ANSWER = 5;

    public const PERIODS = ['month' => 'هذا الشهر', 'all' => 'كل الأوقات'];

    public function __construct(private readonly SettingsService $settings) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('show_leaderboard', true);
    }

    /**
     * @return list<array{rank: int, name: string, grade: string|null, points: int, completed: int, correct: int}>
     */
    public function top(string $period = 'month', int $limit = 10): array
    {
        $period = array_key_exists($period, self::PERIODS) ? $period : 'month';

        return Cache::remember("leaderboard.{$period}.{$limit}", now()->addMinutes(10), fn () => $this->compute($this->since($period), $limit));
    }

    private function since(string $period): ?Carbon
    {
        return $period === 'month' ? now()->startOfMonth() : null;
    }

    /**
     * @return list<array{rank: int, name: string, grade: string|null, points: int, completed: int, correct: int}>
     */
    private function compute(?Carbon $since, int $limit): array
    {
        $students = DB::table('users')
            ->join('model_has_roles', fn ($j) => $j->on('model_has_roles.model_id', '=', 'users.id')->where('model_has_roles.model_type', User::class))
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('roles.name', 'student')
            ->where('users.status', UserStatus::ACTIVE->value)
            ->select('users.id');

        $completed = DB::table('student_content_progress')->whereNotNull('completed_at')
            ->when($since, fn ($q) => $q->where('completed_at', '>=', $since))
            ->whereIn('student_id', $students)
            ->groupBy('student_id')->selectRaw('student_id, count(*) as n')->pluck('n', 'student_id');

        $correct = DB::table('challenge_answers')->where('is_correct', true)
            ->when($since, fn ($q) => $q->where('answered_at', '>=', $since))
            ->whereIn('student_id', $students)
            ->groupBy('student_id')->selectRaw('student_id, count(*) as n')->pluck('n', 'student_id');

        $scores = collect($completed->keys())->merge($correct->keys())->unique()
            ->map(fn ($id) => [
                'id' => (int) $id,
                'completed' => (int) ($completed[$id] ?? 0),
                'correct' => (int) ($correct[$id] ?? 0),
            ])
            ->map(fn ($s) => $s + ['points' => $s['completed'] * self::POINTS_PER_COMPLETION + $s['correct'] * self::POINTS_PER_CORRECT_ANSWER])
            ->filter(fn ($s) => $s['points'] > 0)
            ->sortBy([['points', 'desc'], ['completed', 'desc'], ['id', 'asc']])
            ->take($limit)->values();

        $users = User::with('studentProfile.classroom.grade')->whereIn('id', $scores->pluck('id'))->get()->keyBy('id');

        return $scores->map(fn ($s, $i) => [
            'rank' => $i + 1,
            'name' => $this->publicName($users[$s['id']]->name),
            'grade' => $users[$s['id']]->studentProfile?->classroom?->grade?->name,
            'points' => $s['points'],
            'completed' => $s['completed'],
            'correct' => $s['correct'],
        ])->all();
    }

    /**
     * «سارة أحمد العتيبي» → «سارة ع.» (first name and the initial of the family name)
     */
    public function publicName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $first = $parts[0] ?? '';
        $last = end($parts);

        // Skip the definite article: «العتيبي» → «ع» rather than «ا».
        $family = preg_replace('/^ال(?=\X{2})/u', '', (string) $last);

        return count($parts) > 1 ? $first.' '.mb_substr($family, 0, 1).'.' : $first;
    }
}
