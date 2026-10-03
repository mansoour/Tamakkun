<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Favorite;
use App\Models\User;

class FavoriteService
{
    public function isFavorite(User $student, Content $content): bool
    {
        return Favorite::where('student_id', $student->id)->where('content_id', $content->id)->exists();
    }

    /**
     * @return bool whether the content is now a favorite
     */
    public function toggle(User $student, Content $content): bool
    {
        $deleted = Favorite::where('student_id', $student->id)->where('content_id', $content->id)->delete();

        if ($deleted > 0) {
            return false;
        }

        Favorite::create(['student_id' => $student->id, 'content_id' => $content->id]);

        return true;
    }
}
