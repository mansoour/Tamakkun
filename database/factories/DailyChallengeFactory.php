<?php

namespace Database\Factories;

use App\Enums\ContentSection;
use App\Enums\QuestionType;
use App\Models\DailyChallenge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Fake challenge for today with one quantitative and one verbal question.
 * In each question the first option is correct.
 *
 * @extends Factory<DailyChallenge>
 */
class DailyChallengeFactory extends Factory
{
    public function definition(): array
    {
        return ['challenge_date' => today(), 'is_published' => true];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (DailyChallenge $challenge) {
            foreach ([ContentSection::QUANTITATIVE, ContentSection::VERBAL] as $i => $section) {
                $question = $challenge->questions()->create([
                    'section' => $section, 'question_type' => QuestionType::MULTIPLE_CHOICE,
                    'prompt' => "سؤال تجريبي {$i}", 'explanation' => "شرح تجريبي {$i}", 'sort_order' => $i,
                ]);

                foreach (['أ', 'ب', 'ج', 'د'] as $j => $label) {
                    $question->options()->create(['label' => "خيار {$label} {$i}", 'is_correct' => $j === 0, 'sort_order' => $j]);
                }
            }
        });
    }

    public function draft(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
