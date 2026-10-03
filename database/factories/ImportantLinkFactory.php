<?php

namespace Database\Factories;

use App\Enums\LinkCategory;
use App\Models\ImportantLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Uses example.com (reserved for documentation) — never a real service URL.
 *
 * @extends Factory<ImportantLink>
 */
class ImportantLinkFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => 'رابط تجريبي '.fake()->unique()->numberBetween(1, 99999),
            'url' => 'https://example.com/'.fake()->slug(2),
            'category' => LinkCategory::QIYAS,
            'is_official' => false,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
