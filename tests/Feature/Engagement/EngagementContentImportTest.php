<?php

namespace Tests\Feature\Engagement;

use App\Models\DailyChallenge;
use App\Models\Motivation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EngagementContentImportTest extends TestCase
{
    use RefreshDatabase;

    private function import(?Carbon $start = null): void
    {
        (require database_path('migrations/2026_10_16_000001_import_daily_challenges_and_motivations.php'))->importContent($start);
    }

    public function test_challenges_are_scheduled_one_per_day_from_today_and_imported_once(): void
    {
        $data = require database_path('data/daily-challenges.php');

        $this->import();
        $this->import();

        $challenges = DailyChallenge::with('questions.options')->orderBy('challenge_date')->get();
        $this->assertCount(count($data), $challenges);
        $this->assertTrue($challenges->first()->challenge_date->isToday());
        $this->assertTrue($challenges->last()->challenge_date->isSameDay(today()->addDays(count($data) - 1)));

        foreach ($challenges as $challenge) {
            $this->assertTrue($challenge->is_published);
            $this->assertSame(['quantitative', 'verbal', 'tahsili'], $challenge->questions->map(fn ($q) => $q->section->value)->all());
            foreach ($challenge->questions as $question) {
                $this->assertSame(1, $question->options->where('is_correct', true)->count(), $question->prompt);
                $this->assertNotEmpty($question->explanation);
            }
        }
    }

    public function test_dates_already_taken_are_skipped(): void
    {
        DailyChallenge::factory()->create(['challenge_date' => today()]);

        $this->import();

        $this->assertSame('تحدي اليوم (1): النسبة المئوية', DailyChallenge::whereDate('challenge_date', today()->addDay())->value('title'));
    }

    public function test_student_sees_todays_challenge_and_a_motivation(): void
    {
        $this->import();
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get('/student/challenge')->assertOk()
            ->assertSee('ارتفع سعر كتاب من 80 ريالًا إلى 100 ريال');

        $this->assertSame(count(require database_path('data/motivations.php')), Motivation::where('is_active', true)->whereNull('publish_date')->count());
        $this->actingAs($student)->get('/student/motivation')->assertOk();
    }
}
