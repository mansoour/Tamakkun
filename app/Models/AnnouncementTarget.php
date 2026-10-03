<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * target_type: counselor (her assigned students), classroom or student.
 */
#[Fillable(['announcement_id', 'target_type', 'target_id'])]
class AnnouncementTarget extends Model
{
    public $timestamps = false;
}
