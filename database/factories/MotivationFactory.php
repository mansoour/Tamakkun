<?php

namespace Database\Factories;

use App\Enums\MotivationType;
use App\Models\Motivation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Motivation>
 */
class MotivationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'دفعة تجريبية '.fake()->unique()->numberBetween(1, 99999),
            'content' => 'اختاري مهارة واحدة، شاهدي شرحًا قصيرًا ثم حلي 5 أسئلة.',
            'media_type' => MotivationType::TASK,
            'is_active' => true,
        ];
    }
}
