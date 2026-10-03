<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Key/value platform settings. Read and write them through
 * App\Services\SettingsService so the cache stays consistent.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    //
}
