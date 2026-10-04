<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Real تحدي اليوم and دفعة اليوم content (database/data/daily-challenges.php
 * and database/data/motivations.php).
 *
 * Challenges are scheduled one per day starting on the day the migration runs,
 * skipping dates that already have a challenge. Both imports are idempotent by
 * title, so admin edits and existing records are never overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Feature tests build their own challenges; EngagementContentImportTest calls importContent().
        if (! app()->runningUnitTests()) {
            $this->importContent();
        }
    }

    public function importContent(?Carbon $start = null): void
    {
        $now = now();
        $date = ($start ?? today())->copy();

        foreach (require database_path('data/daily-challenges.php') as $challenge) {
            if (DB::table('daily_challenges')->where('title', $challenge['title'])->exists()) {
                continue;
            }

            while (DB::table('daily_challenges')->whereDate('challenge_date', $date)->exists()) {
                $date->addDay();
            }

            $challengeId = DB::table('daily_challenges')->insertGetId([
                'challenge_date' => $date->toDateString(), 'title' => $challenge['title'], 'is_published' => true,
                'created_at' => $now, 'updated_at' => $now,
            ]);
            $date->addDay();

            foreach ($challenge['questions'] as $i => $question) {
                $questionId = DB::table('challenge_questions')->insertGetId([
                    'daily_challenge_id' => $challengeId, 'section' => $question['section'], 'question_type' => $question['question_type'],
                    'prompt' => $question['prompt'], 'explanation' => $question['explanation'], 'sort_order' => $i,
                    'created_at' => $now, 'updated_at' => $now,
                ]);

                DB::table('challenge_options')->insert(array_map(fn ($label, $j) => [
                    'challenge_question_id' => $questionId, 'label' => $label, 'is_correct' => $j === $question['correct'],
                    'sort_order' => $j, 'created_at' => $now, 'updated_at' => $now,
                ], $question['options'], array_keys($question['options'])));
            }
        }

        foreach (require database_path('data/motivations.php') as $motivation) {
            if (! DB::table('motivations')->where('title', $motivation['title'])->exists()) {
                DB::table('motivations')->insert($motivation + ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('daily_challenges')->whereIn('title', array_column(require database_path('data/daily-challenges.php'), 'title'))
            ->whereNotExists(fn ($q) => $q->from('challenge_questions')->join('challenge_answers', 'challenge_answers.challenge_question_id', '=', 'challenge_questions.id')
                ->whereColumn('challenge_questions.daily_challenge_id', 'daily_challenges.id'))
            ->delete();
        DB::table('motivations')->whereIn('title', array_column(require database_path('data/motivations.php'), 'title'))->delete();
    }
};
