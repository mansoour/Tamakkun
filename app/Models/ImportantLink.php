<?php

namespace App\Models;

use App\Enums\LinkCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Entry on the "روابط مهمة" page. Only verified URLs may be added;
 * `is_official` shows the "مصدر رسمي" badge.
 */
#[Fillable(['title', 'description', 'url', 'icon', 'category', 'is_official', 'sort_order', 'is_active'])]
class ImportantLink extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'category' => LinkCategory::class,
            'is_official' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
