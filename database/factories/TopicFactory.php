<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Topic>
 */
class TopicFactory extends Factory
{
    public function definition(): array
    {
        return ['chapter_id' => Chapter::factory(), 'name' => 'موضوع تجريبي '.fake()->unique()->numberBetween(1, 99999), 'sort_order' => 0];
    }
}
